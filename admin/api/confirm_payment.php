<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão do admin expirada. Faça login no painel novamente.']);
    exit;
}

$db = getDBConnection();
$orderId = intval($_REQUEST['order_id'] ?? $_REQUEST['id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de pedido inválido']);
    exit;
}

try {
    $baseOffset = 522837993;
    $rawId = ($orderId > $baseOffset) ? ($orderId - $baseOffset) : $orderId;

    $stmt = $db->prepare("UPDATE orders SET payment_status = 'Pago' WHERE id = :id OR id = :raw_id");
    $stmt->execute(['id' => $orderId, 'raw_id' => $rawId]);

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Pedido #' . $orderId . ' marcado como Pago com sucesso!',
        'payment_status' => 'Pago'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
