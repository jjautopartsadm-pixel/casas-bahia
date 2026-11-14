<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';

requireLogin();

$db = getDBConnection();
$orderId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if (!$orderId) {
    echo json_encode(['status' => 'error', 'message' => 'ID do pedido inválido.']);
    exit;
}

try {
    $stmt = $db->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->execute(['id' => $orderId]);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: ../index.php?msg=order_deleted");
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => "Pedido #{$orderId} excluído com sucesso!"]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => "Erro ao excluir pedido: " . $e->getMessage()]);
}
