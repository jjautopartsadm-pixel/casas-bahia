<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$orderId = intval($_REQUEST['order_id'] ?? $_REQUEST['id'] ?? 0);

if ($orderId <= 0) {
    try {
        $stmtLast = $db->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
        $lastRow = $stmtLast->fetch();
        if ($lastRow && !empty($lastRow['id'])) {
            $orderId = intval($lastRow['id']);
        }
    } catch(Exception $e) {}
}

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
