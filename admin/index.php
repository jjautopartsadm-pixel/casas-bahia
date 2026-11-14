<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();

$orders = [];
$liveStoreVisitors = 0;
$liveCheckouts = 0;
$todayVisits = 0;
$pixCount = 0;
$totalRevenue = '0,00';
$error = '';
$msg = $_GET['msg'] ?? '';

try {
    $resetTimeFile = __DIR__ . '/../config/visit_reset.time';
    $resetTime = file_exists($resetTimeFile) ? trim(file_get_contents($resetTimeFile)) : '2000-01-01 00:00:00';

    date_default_timezone_set('America/Sao_Paulo');
    $todayStr = date('Y-m-d');
    $time45sAgo = date('Y-m-d H:i:s', time() - 45);

    // 1. VISITANTES AO VIVO NA LOJA REAIS
    $stmtLive = $db->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM site_activity 
        WHERE last_active >= :t45
          AND last_active > :rst
    ");
    $stmtLive->execute(['t45' => $time45sAgo, 'rst' => $resetTime]);
    $liveStoreVisitors = (int)$stmtLive->fetchColumn();

    // 2. CHECKOUT AO VIVO REAIS
    $stmtChk = $db->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM site_activity 
        WHERE is_checkout = 1 
          AND last_active >= :t45
          AND last_active > :rst
    ");
    $stmtChk->execute(['t45' => $time45sAgo, 'rst' => $resetTime]);
    $liveCheckouts = (int)$stmtChk->fetchColumn();

    // 3. VISITAS TOTAIS REAIS NO DIA (Horário de Brasília - Reseta às 00:00 meia-noite)
    $stmtVis = $db->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM site_activity 
        WHERE DATE(last_active) = :todayBR
          AND last_active > :rst
    ");
    $stmtVis->execute(['todayBR' => $todayStr, 'rst' => $resetTime]);
    $todayVisits = (int)$stmtVis->fetchColumn();

    // 4. PIX GERADOS REAIS NO DIA (Horário de Brasília - Reseta às 00:00 meia-noite)
    $stmtPix = $db->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE payment_method LIKE '%Pix%' 
          AND DATE(created_at) = :todayBR
    ");
    $stmtPix->execute(['todayBR' => $todayStr]);
    $pixCount = (int)$stmtPix->fetchColumn();

    // 5. FATURAMENTO TOTAL REAL NO DIA (Horário de Brasília - Reseta às 00:00 meia-noite)
    $stmtRev = $db->prepare("
        SELECT COALESCE(SUM(product_price), 0) 
        FROM orders 
        WHERE (payment_status = 'Pago' OR payment_status = 'Pagamento Confirmado' OR payment_status = 'Aprovado' OR LOWER(payment_status) LIKE '%pago%')
          AND DATE(created_at) = :todayBR
    ");
    $stmtRev->execute(['todayBR' => $todayStr]);
    $totalRev = (float)$stmtRev->fetchColumn();
    $totalRevenue = number_format($totalRev, 2, ',', '.');

    // 6. PEDIDOS RECENTES REAIS
    $stmtOrd = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 50");
    $orders = $stmtOrd->fetchAll();
} catch (Exception $e) {
    $error = "Erro no Dashboard: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --primary-dark: #002266;
      --danger: #e30613;
      --success: #168019;
      --bg-gray: #f4f6f9;
      --border-color: #e2e8f0;
      --sidebar-width: 240px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background-color: var(--bg-gray); color: #333333; display: flex; min-height: 100vh; }

    /* SIDEBAR (BARRA LATERAL) */
    .admin-sidebar { width: var(--sidebar-width); background: #001a4d; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: 4px 0 12px rgba(0,0,0,0.05); }
    .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .brand-logo-admin { font-size: 20px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px; }
    .brand-logo-admin span { color: var(--danger); }
    .sidebar-badge-tag { font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

    .sidebar-nav { padding: 20px 0; flex: 1; }
    .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
    .sidebar-nav li a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #cbd5e1; text-decoration: none; font-size: 15px; font-weight: 600; transition: all 0.2s ease; border-left: 4px solid transparent; }
    .sidebar-nav li a:hover, .sidebar-nav li a.active { background: rgba(255,255,255,0.08); color: #ffffff; border-left-color: #38bdf8; }

    .sidebar-user-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; }
    .user-info { display: flex; align-items: center; gap: 10px; }
    .user-avatar-circle { width: 34px; height: 34px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }
    .user-details { display: flex; flex-direction: column; }
    .user-name { font-size: 13px; font-weight: 700; color: #ffffff; }
    .user-role { font-size: 11px; color: #4ade80; }
    .btn-sidebar-logout { color: #94a3b8; display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; text-decoration: none; }
    .btn-sidebar-logout:hover { color: #ffffff; background: rgba(255,255,255,0.1); }

    /* LAYOUT DE CONTEÚDO PRINCIPAL */
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: calc(100vw - var(--sidebar-width)); }

    /* CABEÇALHO DA PÁGINA */
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; }
    .live-badge-status { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; }
    .live-dot-pulse { width: 8px; height: 8px; background: #16a34a; border-radius: 50%; box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); animation: pulseGreen 1.5s infinite; }
    
    @keyframes pulseGreen {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    /* CARDS DE MÉTRICAS 100% REAIS DO BANCO */
    .live-metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 36px; }
    .metric-card { background: #ffffff; border-radius: 12px; padding: 20px 18px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 14px; transition: transform 0.2s ease; }
    .metric-card:hover { transform: translateY(-2px); }
    
    .metric-icon-box { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .icon-green-pulse { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .icon-blue { background: #eff6ff; color: #2563eb; }
    .icon-green { background: #f0fdf4; color: #16a34a; }
    .icon-orange { background: #fff7ed; color: #ea580c; }
    .icon-purple { background: #faf5ff; color: #9333ea; }
    
    .metric-info { display: flex; flex-direction: column; }
    .metric-value { font-size: 25px; font-weight: 900; color: #0f172a; line-height: 1.1; }
    .metric-label { font-size: 13px; color: #64748b; font-weight: 600; margin-top: 4px; }
    .metric-sub { font-size: 11px; color: #16a34a; font-weight: 700; margin-top: 2px; }

    /* BOTÃO DE LIXEIRA PARA ZERAR VISITAS */
    .btn-clear-visits { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; }
    .btn-clear-visits:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25); }

    /* SEÇÃO DE PEDIDOS DO DASHBOARD */
    .section-card-table { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); overflow: hidden; }
    .table-header-bar { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
    .table-title { font-size: 18px; font-weight: 800; color: #0f172a; }

    .orders-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .orders-table th { background: #f8fafc; padding: 14px 18px; border-bottom: 2px solid #e2e8f0; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .orders-table td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .orders-table tr:hover { background: #f8fafc; }

    .order-id-tag { font-weight: 800; color: var(--primary); }
    .client-details-col { display: flex; flex-direction: column; }
    .client-name-txt { font-weight: 700; color: #1e293b; font-size: 14px; }
    .client-sub-info { font-size: 12px; color: #64748b; margin-top: 2px; }

    .product-details-col { display: flex; flex-direction: column; }
    .product-title-txt { font-weight: 600; color: #334155; font-size: 14px; line-height: 1.3; }
    .product-price-txt { font-weight: 800; color: var(--primary); font-size: 13px; margin-top: 4px; }

    .shipping-addr-box { font-size: 13px; color: #475569; line-height: 1.35; max-width: 260px; }

    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 800; }
    .status-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .status-paid { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
    .empty-orders-cell { padding: 30px; text-align: center; color: #64748b; font-weight: 600; }

    /* BOTÃO DE LIXEIRA (EXCLUIR PEDIDO) */
    .btn-delete-order { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; }
    .btn-delete-order:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25); }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL DO DASHBOARD -->
  <div class="admin-main-wrapper">
    
    <div class="dashboard-header">
      <div>
        <h1 class="page-main-title">Dashboard de Vendas</h1>
      </div>

      <div class="live-badge-status">
        <span class="live-dot-pulse"></span>
        <span>SISTEMA AO VIVO</span>
      </div>
    </div>

    <?php if ($msg === 'visitas_zeradas'): ?>
      <div class="alert-success">✓ Histórico de visitas zerado com sucesso!</div>
    <?php endif; ?>

    <!-- CARDS DE MÉTRICAS 100% REAIS -->
    <div class="live-metrics-grid">
      <!-- 1. VISITANTES AO VIVO NA LOJA -->
      <div class="metric-card">
        <div class="metric-icon-box icon-green-pulse">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="metric-info">
          <span class="metric-value" id="liveStoreVisitorsVal"><?= $liveStoreVisitors ?></span>
          <span class="metric-label">Ao vivo na loja</span>
          <span class="metric-sub">Online agora no site</span>
        </div>
      </div>

      <!-- 2. CHECKOUT AO VIVO -->
      <div class="metric-card">
        <div class="metric-icon-box icon-green">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="metric-info">
          <span class="metric-value" id="liveCheckoutVal"><?= $liveCheckouts ?></span>
          <span class="metric-label">Checkout ao vivo</span>
          <span class="metric-sub">Preenchendo pagamento agora</span>
        </div>
      </div>

      <!-- 3. VISITAS TOTAIS COM BOTÃO DE LIXEIRA PARA ZERAR O HISTÓRICO -->
      <div class="metric-card">
        <div class="metric-icon-box icon-blue">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <div class="metric-info" style="flex: 1;">
          <div style="display: flex; align-items: center; justify-content: space-between;">
            <span class="metric-value" id="todayVisitsVal"><?= number_format($todayVisits, 0, ',', '.') ?></span>
            <button class="btn-clear-visits" onclick="clearVisitsHistory()" title="Zerar e limpar histórico de visitas">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </button>
          </div>
          <span class="metric-label">Visitas Totais</span>
          <span class="metric-sub">Acessos acumulados hoje</span>
        </div>
      </div>

      <!-- 4. PIX GERADOS -->
      <div class="metric-card">
        <div class="metric-icon-box icon-orange">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <div class="metric-info">
          <span class="metric-value" id="pixCountVal"><?= $pixCount ?></span>
          <span class="metric-label">Pix Gerados</span>
          <span class="metric-sub">Aprovação instantânea</span>
        </div>
      </div>

      <!-- 5. FATURAMENTO -->
      <div class="metric-card">
        <div class="metric-icon-box icon-purple">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="metric-info">
          <span class="metric-value" id="totalRevVal">R$ <?= $totalRevenue ?></span>
          <span class="metric-label">Faturamento Hoje</span>
          <span class="metric-sub">Vendas confirmadas</span>
        </div>
      </div>
    </div>

    <!-- SEÇÃO "PEDIDOS" REAIS -->
    <div class="section-card-table">
      <div class="table-header-bar">
        <h2 class="table-title">Pedidos Recentes</h2>
        <span style="font-size: 13px; color: #16a34a; font-weight: 700;">● 100% Sincronizado com o Banco de Dados</span>
      </div>

      <table class="orders-table">
        <thead>
          <tr>
            <th>ID Pedido</th>
            <th>Cliente</th>
            <th>Produto Adquirido</th>
            <th>Endereço Completo de Entrega</th>
            <th>Pagamento</th>
            <th>Status</th>
            <th>Data</th>
            <th style="text-align: center;">Ações</th>
          </tr>
        </thead>
        <tbody id="ordersTableBody">
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="8" class="empty-orders-cell">
                Nenhum pedido recebido até o momento. Aguardando novos acessos ao checkout.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $ord): ?>
              <tr>
                <td><span class="order-id-tag">#<?= htmlspecialchars($ord['id']) ?></span></td>
                
                <td>
                  <div class="client-details-col">
                    <span class="client-name-txt"><?= htmlspecialchars($ord['client_name']) ?></span>
                    <span class="client-sub-info">CPF: <?= htmlspecialchars($ord['client_cpf']) ?></span>
                    <span class="client-sub-info"><?= htmlspecialchars($ord['client_phone']) ?></span>
                  </div>
                </td>

                <td>
                  <div class="product-details-col">
                    <span class="product-title-txt"><?= htmlspecialchars($ord['product_title']) ?></span>
                    <span class="product-price-txt">R$ <?= number_format($ord['product_price'], 2, ',', '.') ?></span>
                  </div>
                </td>

                <td>
                  <div class="shipping-addr-box">
                    <?= htmlspecialchars($ord['shipping_address']) ?>
                  </div>
                </td>

                <td>
                  <strong style="color: #003399; font-size: 13px;"><?= htmlspecialchars($ord['payment_method']) ?></strong>
                </td>

                <td>
                  <?php 
                  $st = $ord['payment_status'] ?? '';
                  $isPaid = (strcasecmp($st, 'Pago') === 0 || strcasecmp($st, 'Pagamento Confirmado') === 0 || strcasecmp($st, 'Aprovado') === 0 || stripos($st, 'pago') !== false);
                  if ($isPaid): 
                  ?>
                    <span class="status-badge status-paid" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">🟢 Pago</span>
                  <?php else: ?>
                    <span class="status-badge status-pending" style="background: #fef9c3; color: #854d0e; border: 1px solid #fef08a;">🟡 Aguardando</span>
                  <?php endif; ?>
                </td>

                <td style="font-size: 13px; color: #64748b; font-weight: 500;">
                  <?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?>
                </td>

                <!-- COLUNA DE AÇÕES (MARCAR COMO PAGO + EXCLUIR) -->
                <td style="text-align: center; white-space: nowrap;">
                  <?php if (!$isPaid): ?>
                    <button type="button" onclick="confirmPayment(<?= $ord['id'] ?>)" title="Marcar Pix como Pago no Admin" style="background: #168019; color: #ffffff; border: none; padding: 6px 10px; border-radius: 6px; font-weight: 800; font-size: 11px; cursor: pointer; margin-right: 4px; display: inline-flex; align-items: center; gap: 4px;">
                      🟢 Marcar como Pago
                    </button>
                  <?php else: ?>
                    <span style="font-size: 11px; font-weight: 800; color: #168019; margin-right: 6px;">✓ Aprovado</span>
                  <?php endif; ?>

                  <button class="btn-delete-order" onclick="deleteOrder(<?= $ord['id'] ?>)" title="Excluir este pedido">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

  <!-- SCRIPT DE AUDITORIA E LIMPEZA AO VIVO VIA AJAX -->
  <script>
    async function clearVisitsHistory() {
      if (!confirm('Deseja realmente zerar e limpar todo o histórico de visitas do site?')) {
        return;
      }
      try {
        const res = await fetch('api/clear_visits.php', { method: 'POST' });
        const data = await res.json();
        if (data.status === 'success') {
          updateLiveDashboard();
        } else {
          window.location.href = 'clear_visits.php';
        }
      } catch (e) {
        window.location.href = 'clear_visits.php';
      }
    }

    async function deleteOrder(id) {
      if (!confirm('Deseja realmente excluir o pedido #' + id + '? Esta ação não pode ser desfeita.')) {
        return;
      }
      try {
        const res = await fetch('api/delete_order.php?id=' + id, { method: 'POST' });
        const data = await res.json();
        if (data.status === 'success') {
          updateLiveDashboard();
        } else {
          alert(data.message || 'Erro ao excluir pedido.');
        }
      } catch (e) {
        window.location.href = 'order_delete.php?id=' + id;
      }
    }

    async function confirmPayment(id) {
      if (!confirm('Deseja confirmar o pagamento do pedido #' + id + '? O status mudará imediatamente para Pago.')) {
        return;
      }
      try {
        const formData = new FormData();
        formData.append('order_id', id);
        let res = await fetch('api/confirm_payment.php', { method: 'POST', body: formData });
        let data = null;
        try { data = await res.json(); } catch(e1) {}

        if (!data || !data.success) {
          res = await fetch('../api/confirm_payment.php', { method: 'POST', body: formData });
          try { data = await res.json(); } catch(e2) {}
        }

        if (data && data.success) {
          alert('✅ Pedido #' + id + ' marcado como Pago com sucesso!');
          updateLiveDashboard();
        } else {
          alert(data ? (data.error || data.message || 'Erro ao confirmar pagamento.') : 'Erro ao processar resposta do servidor.');
        }
      } catch (e) {
        alert('Erro de conexão ao confirmar pagamento.');
      }
    }

    async function updateLiveDashboard() {
      try {
        const res = await fetch('api/stats.php');
        if (!res.ok) return;
        const data = await res.json();
        
        if (data.status === 'success') {
          if (data.live_store_visitors !== undefined) document.getElementById('liveStoreVisitorsVal').textContent = data.live_store_visitors;
          if (data.live_checkouts !== undefined) document.getElementById('liveCheckoutVal').textContent = data.live_checkouts;
          if (data.today_visits !== undefined) document.getElementById('todayVisitsVal').textContent = Number(data.today_visits).toLocaleString('pt-BR');
          if (data.pix_count !== undefined) document.getElementById('pixCountVal').textContent = data.pix_count;
          if (data.total_revenue !== undefined) document.getElementById('totalRevVal').textContent = 'R$ ' + data.total_revenue;

          var tbody = document.getElementById('ordersTableBody');
          if (data.orders && Array.isArray(data.orders) && data.orders.length > 0) {
            var rowsHtml = '';
            data.orders.forEach(function(ord) {
              var st = (ord.payment_status || '').toLowerCase();
              var isPaid = (st === 'pago' || st === 'pagamento confirmado' || st === 'aprovado' || st.indexOf('pago') !== -1 || st.indexOf('aprovad') !== -1);

              var statusHtml = isPaid 
                ? '<span class="status-badge status-paid" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">🟢 Pago</span>' 
                : '<span class="status-badge status-pending" style="background: #fef9c3; color: #854d0e; border: 1px solid #fef08a;">🟡 Aguardando</span>';
              
              var actionBtn = !isPaid 
                ? `<button type="button" onclick="confirmPayment(${ord.id})" title="Marcar Pix como Pago no Admin" style="background: #168019; color: #ffffff; border: none; padding: 6px 10px; border-radius: 6px; font-weight: 800; font-size: 11px; cursor: pointer; margin-right: 4px; display: inline-flex; align-items: center; gap: 4px;">🟢 Marcar como Pago</button>`
                : `<span style="font-size: 11px; font-weight: 800; color: #168019; margin-right: 6px;">✓ Aprovado</span>`;

              var priceFormatted = parseFloat(ord.product_price).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

              rowsHtml += `
                <tr>
                  <td><span class="order-id-tag">#${ord.id}</span></td>
                  <td>
                    <div class="client-details-col">
                      <span class="client-name-txt">${ord.client_name || 'Cliente'}</span>
                      <span class="client-sub-info">CPF: ${ord.client_cpf || ''}</span>
                      <span class="client-sub-info">${ord.client_phone || ''}</span>
                    </div>
                  </td>
                  <td>
                    <div class="product-details-col">
                      <span class="product-title-txt">${ord.product_title || ''}</span>
                      <span class="product-price-txt">R$ ${priceFormatted}</span>
                    </div>
                  </td>
                  <td>
                    <div class="shipping-addr-box">${ord.shipping_address || ''}</div>
                  </td>
                  <td><strong style="color: #003399; font-size: 13px;">${ord.payment_method || ''}</strong></td>
                  <td>${statusHtml}</td>
                  <td style="font-size: 13px; color: #64748b; font-weight: 500;">${ord.created_at || ''}</td>
                  <td style="text-align: center; white-space: nowrap;">
                    ${actionBtn}
                    <button class="btn-delete-order" onclick="deleteOrder(${ord.id})" title="Excluir este pedido">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                  </td>
                </tr>
              `;
            });
            tbody.innerHTML = rowsHtml;
          } else {
            tbody.innerHTML = `<tr><td colspan="8" class="empty-orders-cell">Nenhum pedido recebido até o momento. Aguardando novos acessos ao checkout.</td></tr>`;
          }
        }
      } catch (err) {}
    }

    // Executar consulta real no banco a cada 3 segundos
    setInterval(updateLiveDashboard, 3000);
  </script>
</body>
</html>
