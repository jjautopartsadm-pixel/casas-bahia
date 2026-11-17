<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

session_start();
$sessId = session_id();
if (empty($sessId)) {
    $sessId = md5(($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . ($_SERVER['HTTP_USER_AGENT'] ?? 'agent'));
}

$page = $_GET['page'] ?? 'home.php';
$isCheckout = ($page === 'carrinho.php' || $page === 'pagamento.php' || $page === 'endereco.php' || $page === 'pix.php' || strpos($page, 'checkout') !== false) ? 1 : 0;

try {
    $db = getDBConnection();
    date_default_timezone_set('America/Sao_Paulo');
    $nowBRT = date('Y-m-d H:i:s');
    
    // Verificar se a sessão já existe na tabela site_activity (100% universal: MySQL, SQLite, Supabase)
    $chkStmt = $db->prepare("SELECT id FROM site_activity WHERE session_id = :sess LIMIT 1");
    $chkStmt->execute(['sess' => $sessId]);
    $existingId = $chkStmt->fetchColumn();

    if ($existingId) {
        $upd = $db->prepare("UPDATE site_activity SET page_name = :page, is_checkout = :chk, last_active = :nowBR WHERE id = :id");
        $upd->execute([
            'page' => $page,
            'chk' => $isCheckout,
            'nowBR' => $nowBRT,
            'id' => $existingId
        ]);
    } else {
        $ins = $db->prepare("INSERT INTO site_activity (session_id, page_name, is_checkout, last_active) VALUES (:sess, :page, :chk, :nowBR)");
        $ins->execute([
            'sess' => $sessId,
            'page' => $page,
            'chk' => $isCheckout,
            'nowBR' => $nowBRT
        ]);
    }
    
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
