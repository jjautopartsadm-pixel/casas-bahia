<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("DELETE FROM access_links WHERE id = :id");
        $stmt->execute(['id' => $id]);
    } catch (Exception $e) {}
}

header('Location: links.php');
exit;
