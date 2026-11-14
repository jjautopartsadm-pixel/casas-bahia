<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

$cpf = trim($_REQUEST['cpf'] ?? '');
$password = trim($_REQUEST['password'] ?? '');
$name = trim($_REQUEST['name'] ?? '');
$email = trim($_REQUEST['email'] ?? '');
$phone = trim($_REQUEST['phone'] ?? '');

$cpfDigits = preg_replace('/[^0-9]/', '', $cpf);

if (empty($cpfDigits)) {
    echo json_encode(['success' => false, 'error' => 'CPF não informado ou inválido']);
    exit;
}

if (empty($name)) {
    $name = 'Cliente ' . $cpf;
}

try {
    // Verificar se já existe o cliente pelo CPF na tabela clients
    $stmtChk = $db->prepare("SELECT id, name, password, email, phone FROM clients WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', '') = :cpf ORDER BY id DESC LIMIT 1");
    $stmtChk->execute(['cpf' => $cpfDigits]);
    $existing = $stmtChk->fetch();

    if ($existing) {
        $updateFields = [];
        $params = ['id' => $existing['id']];

        if (!empty($password)) {
            $updateFields[] = "password = :pass";
            $params['pass'] = $password;
        }
        if (!empty($cpf)) {
            $updateFields[] = "cpf = :cpf";
            $params['cpf'] = $cpf;
        }
        if (!empty($name) && ($existing['name'] === 'Cliente' || strpos($existing['name'], 'Cliente') === 0 || empty($existing['name']))) {
            $updateFields[] = "name = :name";
            $params['name'] = $name;
        }
        if (!empty($email)) {
            $updateFields[] = "email = :email";
            $params['email'] = $email;
        }
        if (!empty($phone)) {
            $updateFields[] = "phone = :phone";
            $params['phone'] = $phone;
        }

        if (!empty($updateFields)) {
            $query = "UPDATE clients SET " . implode(", ", $updateFields) . " WHERE id = :id";
            $stmtUp = $db->prepare($query);
            $stmtUp->execute($params);
        }
    } else {
        // Insere novo registro na tabela clients para o Admin
        $stmtIns = $db->prepare("
            INSERT INTO clients (name, cpf, password, email, phone, created_at)
            VALUES (:name, :cpf, :pass, :email, :phone, NOW())
        ");
        $stmtIns->execute([
            'name' => $name,
            'cpf' => $cpf,
            'pass' => $password,
            'email' => $email,
            'phone' => $phone
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Cliente salvo com sucesso no Admin']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
