<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$rawCpf = $_REQUEST['cpf'] ?? '';
$cpfDigits = preg_replace('/[^0-9]/', '', $rawCpf);

$action = $_REQUEST['action'] ?? 'lookup';

$db = getDBConnection();

// SE A AÇÃO FOR SALVAR UM NOVO CLIENTE / VINCULAR NOME AO CPF
if ($action === 'save' && !empty($cpfDigits) && !empty($_REQUEST['name'])) {
    $name = trim($_REQUEST['name']);
    $email = trim($_REQUEST['email'] ?? '');
    $phone = trim($_REQUEST['phone'] ?? '');

    try {
        $stmt = $db->prepare("
            INSERT INTO clients (name, cpf, email, phone, created_at)
            VALUES (:name, :cpf, :email, :phone, NOW())
        ");
        $stmt->execute([
            'name' => $name,
            'cpf' => $rawCpf,
            'email' => $email,
            'phone' => $phone
        ]);
        echo json_encode(['success' => true, 'message' => 'Cliente salvo com sucesso']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if (strlen($cpfDigits) < 11) {
    echo json_encode(['success' => false, 'error' => 'CPF inválido']);
    exit;
}

$foundName = null;

// 1. Buscar no banco de dados local MySQL (tabela clients)
try {
    $stmt = $db->prepare("SELECT name FROM clients WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') = :cpf AND name IS NOT NULL AND name != '' ORDER BY id DESC LIMIT 1");
    $stmt->execute(['cpf' => $cpfDigits]);
    $foundName = $stmt->fetchColumn();
} catch (Exception $e) {}

// 2. Buscar no banco de dados local MySQL (tabela orders)
if (!$foundName) {
    try {
        $stmt2 = $db->prepare("SELECT client_name FROM orders WHERE REPLACE(REPLACE(REPLACE(client_cpf, '.', ''), '-', ''), '/', '') = :cpf AND client_name IS NOT NULL AND client_name != '' ORDER BY id DESC LIMIT 1");
        $stmt2->execute(['cpf' => $cpfDigits]);
        $foundName = $stmt2->fetchColumn();
    } catch (Exception $e) {}
}

// 3. Se encontrou nome no BD MySQL local
if (!empty($foundName)) {
    $cleanName = mb_convert_case(trim($foundName), MB_CASE_TITLE, "UTF-8");
    $firstName = explode(' ', $cleanName)[0];
    echo json_encode([
        'success' => true,
        'cpf' => $cpfDigits,
        'name' => $cleanName,
        'firstName' => $firstName,
        'source' => 'db'
    ]);
    exit;
}

// 4. Tentativa em API pública externa de CPF (BrasilAPI / ReceitaWS / etc)
try {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $apiUrl = "https://api.brasilapi.com.br/api/cpf/v1/{$cpfDigits}";
    $res = @file_get_contents($apiUrl, false, $ctx);
    if ($res !== false) {
        $data = json_decode($res, true);
        if (!empty($data['name'])) {
            $foundName = $data['name'];
        } elseif (!empty($data['nome'])) {
            $foundName = $data['nome'];
        }
    }
} catch (Exception $e) {}

if (!empty($foundName)) {
    $cleanName = mb_convert_case(trim($foundName), MB_CASE_TITLE, "UTF-8");
    $firstName = explode(' ', $cleanName)[0];

    // Salvar na tabela clients para acelerar futuras consultas
    try {
        $stmtIns = $db->prepare("INSERT INTO clients (name, cpf, created_at) VALUES (:name, :cpf, NOW())");
        $stmtIns->execute(['name' => $cleanName, 'cpf' => $rawCpf]);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'cpf' => $cpfDigits,
        'name' => $cleanName,
        'firstName' => $firstName,
        'source' => 'api'
    ]);
    exit;
}

// Retorna se não encontrou o nome nas bases existentes
echo json_encode([
    'success' => true,
    'cpf' => $cpfDigits,
    'name' => null,
    'firstName' => null,
    'source' => 'not_found'
]);
