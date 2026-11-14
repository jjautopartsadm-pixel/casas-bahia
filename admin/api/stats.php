<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';

requireLogin();

$db = getDBConnection();

try {
    // Carregar timestamp do último reset de visitas
    $resetTimeFile = __DIR__ . '/../../config/visit_reset.time';
    $resetTime = file_exists($resetTimeFile) ? trim(file_get_contents($resetTimeFile)) : '2000-01-01 00:00:00';

    date_default_timezone_set('America/Sao_Paulo');
    $todayStr = date('Y-m-d');
    $time45sAgo = date('Y-m-d H:i:s', time() - 45);

    // 1. VISITANTES AO VIVO NA LOJA (Sessões ativas nos últimos 45 segundos após o reset)
    $stmtLive = $db->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM site_activity 
        WHERE last_active >= :t45
          AND last_active > :rst
    ");
    $stmtLive->execute(['t45' => $time45sAgo, 'rst' => $resetTime]);
    $liveStoreVisitors = (int)$stmtLive->fetchColumn();

    // 2. CHECKOUT AO VIVO (Sessões ativas em carrinho.php ou pagamento.php após o reset)
    $stmtChk = $db->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM site_activity 
        WHERE is_checkout = 1 
          AND last_active >= :t45
          AND last_active > :rst
    ");
    $stmtChk->execute(['t45' => $time45sAgo, 'rst' => $resetTime]);
    $liveCheckouts = (int)$stmtChk->fetchColumn();

    // 3. VISITAS TOTAIS NO DIA (Horário de Brasília - Reseta às 00:00 meia-noite)
    $stmtVis = $db->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM site_activity 
        WHERE DATE(last_active) = :todayBR
          AND last_active > :rst
    ");
    $stmtVis->execute(['todayBR' => $todayStr, 'rst' => $resetTime]);
    $todayVisits = (int)$stmtVis->fetchColumn();

    // 4. PIX GERADOS NO DIA (Horário de Brasília - Reseta às 00:00 meia-noite)
    $stmtPix = $db->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE payment_method LIKE '%Pix%' 
          AND DATE(created_at) = :todayBR
    ");
    $stmtPix->execute(['todayBR' => $todayStr]);
    $pixCount = (int)$stmtPix->fetchColumn();

    // 5. FATURAMENTO TOTAL NO DIA (Horário de Brasília - Reseta às 00:00 meia-noite)
    $stmtRev = $db->prepare("
        SELECT COALESCE(SUM(product_price), 0) 
        FROM orders 
        WHERE (payment_status = 'Pago' OR payment_status = 'Pagamento Confirmado' OR payment_status = 'Aprovado' OR LOWER(payment_status) LIKE '%pago%')
          AND DATE(created_at) = :todayBR
    ");
    $stmtRev->execute(['todayBR' => $todayStr]);
    $totalRev = (float)$stmtRev->fetchColumn();

    // 6. PEDIDOS RECENTES (REAIS)
    $stmtOrd = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 50");
    $orders = $stmtOrd->fetchAll();

    echo json_encode([
        'status' => 'success',
        'live_store_visitors' => $liveStoreVisitors,
        'live_checkouts' => $liveCheckouts,
        'today_visits' => $todayVisits,
        'pix_count' => $pixCount,
        'total_revenue' => number_format($totalRev, 2, ',', '.'),
        'orders' => $orders
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
