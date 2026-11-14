<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: index.php?msg=" . urlencode("Produto excluído com sucesso."));
        exit;
    } catch (Exception $e) {
        header("Location: index.php?msg=" . urlencode("Erro ao excluir produto: " . $e->getMessage()));
        exit;
    }
}

header("Location: index.php");
exit;
