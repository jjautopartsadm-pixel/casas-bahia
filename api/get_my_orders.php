<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$cpf = trim($_REQUEST['cpf'] ?? '');
$cpfDigits = preg_replace('/[^0-9]/', '', $cpf);

$orders = [];

try {
    if (!empty($cpfDigits)) {
        $stmt = $db->prepare("
            SELECT o.*, 
                   COALESCE(
                       (SELECT image_url FROM product_images WHERE product_id = o.product_id ORDER BY is_main DESC, id ASC LIMIT 1),
                       'assets/images/product_aoc_main.png'
                   ) AS main_image
            FROM orders o
            WHERE REPLACE(REPLACE(REPLACE(o.client_cpf, '.', ''), '-', ''), '/', '') = :cpf 
            ORDER BY o.id DESC
        ");
        $stmt->execute(['cpf' => $cpfDigits]);
        $orders = $stmt->fetchAll();
    } else {
        $stmt = $db->query("
            SELECT o.*, 
                   COALESCE(
                       (SELECT image_url FROM product_images WHERE product_id = o.product_id ORDER BY is_main DESC, id ASC LIMIT 1),
                       'assets/images/product_aoc_main.png'
                   ) AS main_image
            FROM orders o 
            ORDER BY o.id DESC 
            LIMIT 10
        ");
        $orders = $stmt->fetchAll();
    }
} catch (Exception $e) {}

echo json_encode([
    'success' => true,
    'orders' => $orders
]);
