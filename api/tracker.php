<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

session_start();
$sessId = session_id();
if (empty($sessId)) {
    $sessId = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
}

$page = $_GET['page'] ?? 'index.php';
$isCheckout = ($page === 'carrinho.php' || $page === 'pagamento.php' || strpos($page, 'checkout') !== false) ? 1 : 0;

try {
    $db = getDBConnection();
    
    $nowBRT = date('Y-m-d H:i:s');
    // Atualizar ou inserir atividade da sessão com horário oficial de Brasília (UTC-3)
    $stmt = $db->prepare("
        INSERT INTO site_activity (session_id, page_name, is_checkout, last_active)
        VALUES (:sess, :page, :chk, :nowBR)
        ON DUPLICATE KEY UPDATE 
            page_name = VALUES(page_name),
            is_checkout = VALUES(is_checkout),
            last_active = VALUES(last_active)
    ");
    $stmt->execute([
        'sess' => $sessId,
        'page' => $page,
        'chk' => $isCheckout,
        'nowBR' => $nowBRT
    ]);
    
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
