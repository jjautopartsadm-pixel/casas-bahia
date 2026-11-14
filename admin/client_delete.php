<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$clientId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($clientId > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->execute(['id' => $clientId]);
    } catch (Exception $e) {}
}

header("Location: clientes.php?msg=cliente_excluido");
exit;
