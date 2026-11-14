<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/zapi_service.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';
$testResult = null;
$statusResult = null;

// Processar formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. SALVAR CONFIGURAÇÕES
    if ($action === 'save_settings') {
        $instanceId = trim($_POST['instance_id'] ?? '');
        $instanceToken = trim($_POST['instance_token'] ?? '');
        $clientToken = trim($_POST['client_token'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sendImage = isset($_POST['send_image']) ? 1 : 0;
        $messageTemplate = trim($_POST['message_template'] ?? '');

        try {
            $stmt = $db->prepare("
                INSERT INTO zapi_settings (id, instance_id, instance_token, client_token, is_active, send_image, message_template, updated_at)
                VALUES (1, :iid, :itok, :ctok, :act, :simg, :tpl, NOW())
                ON DUPLICATE KEY UPDATE
                    instance_id = VALUES(instance_id),
                    instance_token = VALUES(instance_token),
                    client_token = VALUES(client_token),
                    is_active = VALUES(is_active),
                    send_image = VALUES(send_image),
                    message_template = VALUES(message_template),
                    updated_at = NOW()
            ");
            $stmt->execute([
                'iid'  => $instanceId,
                'itok' => $instanceToken,
                'ctok' => $clientToken,
                'act'  => $isActive,
                'simg' => $sendImage,
                'tpl'  => $messageTemplate
            ]);
            $message = 'Configurações da Z-API salvas com sucesso!';
        } catch (Exception $e) {
            $error = 'Erro ao salvar configurações: ' . $e->getMessage();
        }
    }

    // 2. TESTAR ENVIO NO WHATSAPP
    else if ($action === 'test_send') {
        $testPhone = trim($_POST['test_phone'] ?? '');
        $settings = ZapiService::getSettings($db);

        if (empty($testPhone)) {
            $error = 'Por favor, digite o número de WhatsApp para teste com DDD.';
        } else if (empty($settings['instance_id']) || empty($settings['instance_token'])) {
            $error = 'Salve o ID e Token da instância antes de realizar o teste.';
        } else {
            $testData = [
                'order_id'       => '522837993',
                'client_name'    => 'Cliente Teste Casas Bahia',
                'product_title'  => 'Smart TV 43 AOC Full HD Wi-Fi Roku TV',
                'pix_price'      => '1580.07',
                'pix_code'       => '00020126580014br.gov.bcb.pix0136casasbahia-teste-pix5204000053039865401580.075802BR5915CASAS BAHIA S A6009SAO PAULO62070503***63041234',
                'client_phone'   => $testPhone,
                'qr_code_url'    => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode('00020126580014br.gov.bcb.pix0136casasbahia-teste-pix5204000053039865401580.075802BR5915CASAS BAHIA S A6009SAO PAULO62070503***63041234'),
                'pix_url'        => 'pix.php?order_id=522837993'
            ];

            $res = ZapiService::sendPixNotification($testData, $db);
            $testResult = $res;

            if (!empty($res['success'])) {
                $message = 'Mensagem de teste enviada com sucesso para ' . htmlspecialchars($testPhone) . '!';
            } else {
                $error = 'Falha ao enviar mensagem de teste. Verifique as credenciais ou a conexão da instância.';
            }
        }
    }

    // 3. VERIFICAR STATUS DA INSTÂNCIA
    else if ($action === 'check_status') {
        $settings = ZapiService::getSettings($db);
        if (empty($settings['instance_id']) || empty($settings['instance_token'])) {
            $error = 'Configure o ID e Token da instância primeiro.';
        } else {
            $statusResult = ZapiService::checkStatus($settings['instance_id'], $settings['instance_token'], $settings['client_token']);
        }
    }

    // 4. LIMPAR LOGS
    else if ($action === 'clear_logs') {
        try {
            $db->exec("DELETE FROM zapi_logs");
            $message = 'Histórico de envios limpo com sucesso!';
        } catch (Exception $e) {}
    }
}

// Carregar configurações atuais
$settings = ZapiService::getSettings($db);

// Carregar logs recentes
$logs = [];
try {
    $stmtLogs = $db->query("SELECT * FROM zapi_logs ORDER BY id DESC LIMIT 15");
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WhatsApp Z-API | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --primary-dark: #002266;
      --wa-green: #25D366;
      --wa-dark: #128C7E;
      --bg-gray: #f4f6f9;
      --sidebar-width: 240px;
      --success: #168019;
      --danger: #e30613;
      --card-border: #e2e8f0;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background-color: var(--bg-gray); color: #333333; display: flex; min-height: 100vh; }

    /* SIDEBAR */
    .admin-sidebar { width: var(--sidebar-width); background: #001a4d; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: 4px 0 12px rgba(0,0,0,0.05); }
    .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .brand-logo-admin { font-size: 20px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px; }
    .brand-logo-admin span { color: #e30613; }
    .sidebar-badge-tag { font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

    .sidebar-nav { padding: 20px 0; flex: 1; overflow-y: auto; }
    .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
    .sidebar-nav li a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s ease; border-left: 4px solid transparent; }
    .sidebar-nav li a:hover, .sidebar-nav li a.active { background: rgba(255,255,255,0.08); color: #ffffff; border-left-color: #25D366; }

    .sidebar-user-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: space-between; }
    .user-avatar-circle { width: 34px; height: 34px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }
    .user-details { display: flex; flex-direction: column; }
    .user-name { font-size: 13px; font-weight: 700; color: #ffffff; }
    .user-role { font-size: 11px; color: #4ade80; }
    .btn-sidebar-logout { color: #94a3b8; display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; text-decoration: none; }

    /* MAIN CONTENT */
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: 1200px; }
    .dashboard-header { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 10px; }

    .grid-2col { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; }
    @media (max-width: 980px) { .grid-2col { grid-template-columns: 1fr; } }

    .admin-card { background: #ffffff; border-radius: 12px; border: 1px solid var(--card-border); padding: 26px 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .card-title { font-size: 17px; font-weight: 800; color: #1e293b; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .form-group .help-text { font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.4; }
    
    .form-control { width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #ffffff; transition: border-color 0.2s; font-family: inherit; }
    .form-control:focus { outline: none; border-color: var(--wa-green); box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15); }
    textarea.form-control { min-height: 140px; line-height: 1.5; font-family: monospace; font-size: 13px; }

    .switch-wrapper { display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 14px 18px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 18px; }
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--wa-green); }
    input:checked + .slider:before { transform: translateX(20px); }

    .btn-submit { background: var(--wa-dark); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; justify-content: center; transition: background-color 0.2s; }
    .btn-submit:hover { background: #075E54; }
    .btn-blue { background: var(--primary); }
    .btn-blue:hover { background: var(--primary-dark); }
    .btn-outline { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
    .btn-outline:hover { background: #f1f5f9; }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 14px 18px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }

    .tags-container { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; margin-bottom: 14px; }
    .tag-badge { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-family: monospace; font-weight: 700; cursor: pointer; transition: all 0.15s; }
    .tag-badge:hover { background: #0284c7; color: #fff; }

    /* WHATSAPP PREVIEW CARD */
    .whatsapp-preview-box { background: #efeae2; background-image: radial-gradient(#d4cdb4 1px, transparent 1px); background-size: 16px 16px; border-radius: 12px; padding: 20px 16px; border: 1px solid #dcd7cc; margin-top: 14px; }
    .wa-bubble { background: #ffffff; border-radius: 8px 8px 8px 0; padding: 12px 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.15); max-width: 90%; font-size: 13px; line-height: 1.5; color: #111b21; position: relative; white-space: pre-wrap; word-break: break-word; }
    .wa-bubble-header { font-size: 11px; font-weight: 700; color: #25D366; margin-bottom: 4px; display: flex; align-items: center; gap: 4px; }
    .wa-time { font-size: 10px; color: #667781; text-align: right; margin-top: 4px; }

    /* TABLE */
    .table-responsive { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    .admin-table th { background: #f8fafc; padding: 10px 14px; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 700; }
    .admin-table td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .admin-table tr:hover td { background: #f8fafc; }
    .badge-status { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-sent { background: #dcfce7; color: #166534; }
    .badge-failed { background: #fee2e2; color: #991b1b; }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="admin-main-wrapper">

    <div class="dashboard-header">
      <div>
        <h1 class="page-main-title">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          Integração WhatsApp Z-API
        </h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">
          Envio automático de Pix Copia e Cola, QR Code e confirmação de pedido para o WhatsApp do cliente.
        </p>
      </div>

      <div style="display: flex; gap: 10px;">
        <a href="https://app.z-api.io/" target="_blank" class="btn-submit btn-outline" style="text-decoration: none; font-size: 13px;">
          🌐 Abrir Painel Z-API ↗
        </a>
      </div>
    </div>

    <!-- MENSAGENS -->
    <?php if (!empty($message)): ?>
      <div class="alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- RESULTADO DO STATUS -->
    <?php if ($statusResult !== null): ?>
      <div class="alert-info" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <strong>Status da Conexão Z-API:</strong>
          <?php if (!empty($statusResult['connected'])): ?>
            <span style="color: #16a34a; font-weight: 800;">🟢 Instância Conectada e Pronta para Envio!</span>
          <?php else: ?>
            <span style="color: #dc2626; font-weight: 800;">🔴 Instância Desconectada ou QR Code Pendente no Z-API.</span>
          <?php endif; ?>
          <div style="font-size: 11px; font-family: monospace; margin-top: 4px; color: #475569;">
            Resposta HTTP <?= $statusResult['http_code'] ?>: <?= htmlspecialchars($statusResult['response']) ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- RESULTADO DO TESTE -->
    <?php if ($testResult !== null): ?>
      <div class="alert-info">
        <strong>Resultado do Teste de Envio:</strong>
        <div style="font-size: 12px; font-family: monospace; margin-top: 6px; background: rgba(0,0,0,0.04); padding: 8px; border-radius: 6px; word-break: break-all;">
          HTTP Code: <?= $testResult['http_code'] ?? 'N/A' ?> | Resposta: <?= htmlspecialchars($testResult['response'] ?? $testResult['error'] ?? '') ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="grid-2col">

      <!-- COLUNA 1: CREDENCIAIS E PERSONALIZAÇÃO -->
      <div>

        <!-- CARD DE CONFIGURAÇÃO DA CONTA Z-API -->
        <div class="admin-card">
          <h2 class="card-title">
            <span>🔑 Credenciais da Instância Z-API</span>
            <form method="POST" style="margin: 0;">
              <input type="hidden" name="action" value="check_status">
              <button type="submit" class="btn-submit btn-outline" style="padding: 6px 12px; font-size: 12px;">🔄 Testar Conexão</button>
            </form>
          </h2>

          <form method="POST" action="whatsapp.php" id="formZapiSettings">
            <input type="hidden" name="action" value="save_settings">

            <div class="switch-wrapper">
              <label class="switch">
                <input type="checkbox" name="is_active" value="1" <?= !empty($settings['is_active']) ? 'checked' : '' ?>>
                <span class="slider"></span>
              </label>
              <div>
                <strong style="font-size: 14px; color: #1e293b;">Envio Automático no WhatsApp Ativado</strong>
                <div style="font-size: 12px; color: #64748b;">Dispara mensagem no WhatsApp assim que o cliente finaliza o checkout Pix.</div>
              </div>
            </div>

            <div class="form-group">
              <label for="instance_id">ID da Instância (Instance ID):</label>
              <input type="text" id="instance_id" name="instance_id" class="form-control" placeholder="Ex: 3B765B9077226019A8B614..." value="<?= htmlspecialchars($settings['instance_id']) ?>" required>
              <div class="help-text">Localizado em seu painel no <a href="https://app.z-api.io/" target="_blank">app.z-api.io</a>.</div>
            </div>

            <div class="form-group">
              <label for="instance_token">Token da Instância (Instance Token):</label>
              <input type="text" id="instance_token" name="instance_token" class="form-control" placeholder="Ex: 4A8B99C23D10..." value="<?= htmlspecialchars($settings['instance_token']) ?>" required>
              <div class="help-text">Token de acesso individual da sua instância Z-API.</div>
            </div>

            <div class="form-group">
              <label for="client_token">Token de Segurança da Conta (Client-Token):</label>
              <input type="text" id="client_token" name="client_token" class="form-control" placeholder="Ex: F18063B29..." value="<?= htmlspecialchars($settings['client_token']) ?>">
              <div class="help-text">Token de segurança global da sua conta Z-API (enviado no header <code>Client-Token</code>).</div>
            </div>

            <div class="switch-wrapper" style="margin-top: 10px;">
              <label class="switch">
                <input type="checkbox" name="send_image" value="1" <?= !empty($settings['send_image']) ? 'checked' : '' ?>>
                <span class="slider"></span>
              </label>
              <div>
                <strong style="font-size: 14px; color: #1e293b;">Enviar Imagem do QR Code do Pix</strong>
                <div style="font-size: 12px; color: #64748b;">Envia a foto do QR Code gerado para o cliente escanear com outro celular.</div>
              </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
              <label for="message_template">✏️ Mensagem Personalizada (Frases & Tags):</label>
              <div class="help-text" style="margin-bottom: 8px;">Clique em uma tag para inseri-la no texto da mensagem:</div>
              
              <div class="tags-container">
                <span class="tag-badge" onclick="insertTag('{nome}')">{nome} (Nome Completo)</span>
                <span class="tag-badge" onclick="insertTag('{primeiro_nome}')">{primeiro_nome}</span>
                <span class="tag-badge" onclick="insertTag('{pedido_id}')">{pedido_id}</span>
                <span class="tag-badge" onclick="insertTag('{produto}')">{produto}</span>
                <span class="tag-badge" onclick="insertTag('{valor}')">{valor}</span>
                <span class="tag-badge" onclick="insertTag('{pix_copia_cola}')">{pix_copia_cola}</span>
                <span class="tag-badge" onclick="insertTag('{link_pix}')">{link_pix}</span>
              </div>

              <textarea id="message_template" name="message_template" class="form-control" rows="8" oninput="updateLivePreview()"><?= htmlspecialchars($settings['message_template']) ?></textarea>
            </div>

            <button type="submit" class="btn-submit" style="width: 100%; padding: 14px; font-size: 15px;">
              💾 Salvar Todas as Configurações Z-API
            </button>
          </form>
        </div>

      </div>

      <!-- COLUNA 2: PREVIEW E TESTE -->
      <div>

        <!-- CARD PREVIEW DA MENSAGEM -->
        <div class="admin-card">
          <h2 class="card-title">👁️ Prévia da Mensagem no WhatsApp</h2>
          <div style="font-size: 12px; color: #64748b; margin-bottom: 10px;">
            Veja em tempo real como o seu cliente receberá o Pix no WhatsApp:
          </div>

          <div class="whatsapp-preview-box">
            <div class="wa-bubble">
              <div class="wa-bubble-header">
                <span>💬 Casas Bahia Oficial</span>
              </div>
              <div id="livePreviewText">Carregando prévia...</div>
              <div class="wa-time"><?= date('H:i') ?> &bull; ✓✓</div>
            </div>
          </div>
        </div>

        <!-- CARD TESTAR DISPARO EM TEMPO REAL -->
        <div class="admin-card">
          <h2 class="card-title">📱 Testar Disparo de Mensagem</h2>
          <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
            Digite o seu número de WhatsApp com DDD para testar o envio das credenciais e frases configuradas.
          </p>

          <form method="POST" action="whatsapp.php">
            <input type="hidden" name="action" value="test_send">

            <div class="form-group">
              <label for="test_phone">Número de WhatsApp (DDD + Número):</label>
              <input type="text" id="test_phone" name="test_phone" class="form-control" placeholder="Ex: 11998765432" required>
              <div class="help-text">Exemplo: 11999999999 ou 48991447795 (sem traços ou espaços).</div>
            </div>

            <button type="submit" class="btn-submit btn-blue" style="width: 100%;">
              🚀 Enviar WhatsApp de Teste Agora
            </button>
          </form>
        </div>

      </div>

    </div>

    <!-- CARD HISTÓRICO DE DISPAROS -->
    <div class="admin-card">
      <div class="card-title">
        <span>📜 Últimos Envios Realizados</span>
        <?php if (!empty($logs)): ?>
          <form method="POST" onsubmit="return confirm('Deseja realmente limpar o histórico de logs?');" style="margin: 0;">
            <input type="hidden" name="action" value="clear_logs">
            <button type="submit" class="btn-submit btn-outline" style="padding: 4px 10px; font-size: 12px;">Limpar Logs</button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (empty($logs)): ?>
        <p style="color: #94a3b8; font-size: 14px; text-align: center; padding: 20px;">
          Nenhum envio registrado ainda. As mensagens disparadas nos checkouts e testes aparecerão aqui.
        </p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Data / Hora</th>
                <th>Pedido</th>
                <th>Telefone</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Resposta da API</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                  <td><strong>#<?= htmlspecialchars($log['order_id'] ?? '-') ?></strong></td>
                  <td><code><?= htmlspecialchars($log['phone']) ?></code></td>
                  <td><span style="text-transform: uppercase; font-size: 11px; font-weight: 700;"><?= htmlspecialchars($log['message_type']) ?></span></td>
                  <td>
                    <?php if ($log['status'] === 'sent'): ?>
                      <span class="badge-status badge-sent">✓ Enviado</span>
                    <?php else: ?>
                      <span class="badge-status badge-failed">✕ Falha</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-family: monospace; font-size: 11px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($log['response_payload'] ?? '') ?>">
                    <?= htmlspecialchars($log['response_payload'] ?? '-') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <script>
    function insertTag(tag) {
      const textarea = document.getElementById('message_template');
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      const text = textarea.value;
      textarea.value = text.substring(0, start) + tag + text.substring(end);
      textarea.focus();
      textarea.selectionStart = textarea.selectionEnd = start + tag.length;
      updateLivePreview();
    }

    function updateLivePreview() {
      const rawText = document.getElementById('message_template').value;
      const previewEl = document.getElementById('livePreviewText');
      
      let parsed = rawText
        .replace(/{nome}/g, 'Jonas Lima Teixeira')
        .replace(/{primeiro_nome}/g, 'Jonas')
        .replace(/{pedido_id}/g, '522837993')
        .replace(/{produto}/g, 'Smart TV 43 AOC Full HD Wi-Fi Roku TV')
        .replace(/{valor}/g, 'R$ 1.580,07')
        .replace(/{cpf}/g, '048.912.481-09')
        .replace(/{pix_copia_cola}/g, '00020126580014br.gov.bcb.pix0136casasbahia-pix-code5204000053039865401580.075802BR5915CASAS BAHIA S A6009SAO PAULO62070503***6304A1B2')
        .replace(/{link_pix}/g, 'pix.php?order_id=522837993');

      // WhatsApp bold formatting *text* -> <strong>text</strong>
      parsed = parsed.replace(/\*([^\*]+)\*/g, '<strong>$1</strong>');

      previewEl.innerHTML = parsed;
    }

    document.addEventListener('DOMContentLoaded', function() {
      updateLivePreview();
    });
  </script>

</body>
</html>
