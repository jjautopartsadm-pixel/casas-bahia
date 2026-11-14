<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();

try {
    $db->exec("DELETE FROM site_activity;");
    file_put_contents(__DIR__ . '/../config/visit_reset.time', date('Y-m-d H:i:s'));
} catch (Exception $e) {}

header("Location: index.php?msg=visitas_zeradas");
exit;
