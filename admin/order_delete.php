<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM orders WHERE id = :id");
        $stmt->execute(['id' => $orderId]);
    } catch (Exception $e) {}
}

header("Location: index.php?msg=pedido_excluido");
exit;
