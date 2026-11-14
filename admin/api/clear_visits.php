<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';

requireLogin();

$db = getDBConnection();

try {
    // 1. Apagar todas as sessões registradas
    $db->exec("DELETE FROM site_activity;");
    
    // 2. Gravar o carimbo de data/hora do reset para zerar permanentemente o contador
    $now = date('Y-m-d H:i:s');
    file_put_contents(__DIR__ . '/../../config/visit_reset.time', $now);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax'])) {
        header("Location: ../index.php?msg=visitas_zeradas");
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Histórico de visitas zerado com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao zerar visitas: ' . $e->getMessage()]);
}
