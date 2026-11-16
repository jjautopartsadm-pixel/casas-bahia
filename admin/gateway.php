<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activeGateway = trim($_POST['active_gateway'] ?? 'unipay');
    $payberryPublicKey = trim($_POST['payberry_public_key'] ?? '');
    $payberrySecretKey = trim($_POST['payberry_secret_key'] ?? '');
    $velanaApiKey = trim($_POST['velana_api_key'] ?? '');
    $velanaSecretKey = trim($_POST['velana_secret_key'] ?? '');
    $sigilopayApiKey = trim($_POST['sigilopay_api_key'] ?? '');
    $sigilopaySecretKey = trim($_POST['sigilopay_secret_key'] ?? '');
    $unipayPublicKey = trim($_POST['unipay_public_key'] ?? '');
    $unipaySecretKey = trim($_POST['unipay_secret_key'] ?? '');
    
    $manualPixType = trim($_POST['manual_pix_type'] ?? 'cpf');
    $manualPixKey = trim($_POST['manual_pix_key'] ?? '');
    $manualPixName = trim($_POST['manual_pix_name'] ?? 'CASAS BAHIA');
    $manualPixCity = trim($_POST['manual_pix_city'] ?? 'SAO PAULO');

    $gatewayName = 'UniPay BR Gateway';
    if ($activeGateway === 'velana') {
        $gatewayName = 'Velana Gateway';
    } else if ($activeGateway === 'sigilopay') {
        $gatewayName = 'SigiloPay Gateway';
    } else if ($activeGateway === 'payberry') {
        $gatewayName = 'Payberry Gateway';
    } else if ($activeGateway === 'manual') {
        $gatewayName = 'Chave Pix Manual';
    }

    $apiKey = ($activeGateway === 'velana') ? $velanaApiKey : (($activeGateway === 'sigilopay') ? $sigilopayApiKey : (($activeGateway === 'unipay') ? $unipayPublicKey : $payberryPublicKey));
    $secretKey = ($activeGateway === 'velana') ? $velanaSecretKey : (($activeGateway === 'sigilopay') ? $sigilopaySecretKey : (($activeGateway === 'unipay') ? $unipaySecretKey : $payberrySecretKey));

    try {
        $check = $db->query("SELECT id FROM gateway_settings WHERE id = 1")->fetch();
        if ($check) {
            $stmt = $db->prepare("
                UPDATE gateway_settings SET 
                    gateway_name = :name, active_gateway = :agw, api_key = :api, secret_key = :secret,
                    payberry_public_key = :ppub, payberry_secret_key = :psec,
                    velana_api_key = :vapi, velana_secret_key = :vsec,
                    sigilopay_api_key = :sapi, sigilopay_secret_key = :ssec,
                    unipay_public_key = :upub, unipay_secret_key = :usec,
                    pix_key = :pixk, manual_pix_type = :mtype, manual_pix_key = :mkey,
                    manual_pix_name = :mname, manual_pix_city = :mcity, active_status = 1
                WHERE id = 1
            ");
        } else {
            $stmt = $db->prepare("
                INSERT INTO gateway_settings 
                    (id, gateway_name, active_gateway, api_key, secret_key, payberry_public_key, payberry_secret_key, velana_api_key, velana_secret_key, sigilopay_api_key, sigilopay_secret_key, unipay_public_key, unipay_secret_key, pix_key, manual_pix_type, manual_pix_key, manual_pix_name, manual_pix_city, active_status)
                VALUES 
                    (1, :name, :agw, :api, :secret, :ppub, :psec, :vapi, :vsec, :sapi, :ssec, :upub, :usec, :pixk, :mtype, :mkey, :mname, :mcity, 1)
            ");
        }
        $stmt->execute([
            'name' => $gatewayName,
            'agw' => $activeGateway,
            'api' => $apiKey,
            'secret' => $secretKey,
            'ppub' => $payberryPublicKey,
            'psec' => $payberrySecretKey,
            'vapi' => $velanaApiKey,
            'vsec' => $velanaSecretKey,
            'sapi' => $sigilopayApiKey,
            'ssec' => $sigilopaySecretKey,
            'upub' => $unipayPublicKey,
            'usec' => $unipaySecretKey,
            'pixk' => $manualPixKey,
            'mtype' => $manualPixType,
            'mkey' => $manualPixKey,
            'mname' => $manualPixName,
            'mcity' => $manualPixCity
        ]);
        $message = "Configurações salvas! Opção ativa no checkout: " . $gatewayName;
    } catch (Exception $e) {
        $error = "Erro ao salvar credenciais: " . $e->getMessage();
    }
}

// Carregar configurações atuais do Banco
$gateway = [
    'active_gateway' => 'unipay',
    'gateway_name' => 'UniPay BR Gateway',
    'payberry_public_key' => getenv('PAYBERRY_PUBLIC_KEY') ?: '',
    'payberry_secret_key' => getenv('PAYBERRY_SECRET_KEY') ?: '',
    'velana_api_key' => getenv('VELANA_API_KEY') ?: '',
    'velana_secret_key' => getenv('VELANA_SECRET_KEY') ?: '',
    'sigilopay_api_key' => 'jonasmgtd2_1qgte20cj4igtcb8',
    'sigilopay_secret_key' => 'mx68p6qujgsetcgfe2z2ui22zqv1c67k6xi5sh4nodkh6njcfgd12as149s5k6xs',
    'unipay_public_key' => '',
    'unipay_secret_key' => '',
    'manual_pix_type' => 'cpf',
    'manual_pix_key' => '',
    'manual_pix_name' => 'CASAS BAHIA',
    'manual_pix_city' => 'SAO PAULO',
    'active_status' => 1
];

try {
    $stmt = $db->query("SELECT * FROM gateway_settings WHERE id = 1 LIMIT 1");
    $dbGateway = $stmt->fetch();
    if ($dbGateway) {
        $gateway = array_merge($gateway, array_filter($dbGateway, function($v) { return $v !== null; }));
        if (empty($gateway['active_gateway'])) {
            if (stripos($gateway['gateway_name'] ?? '', 'UniPay') !== false) {
                $gateway['active_gateway'] = 'unipay';
            } elseif (stripos($gateway['gateway_name'] ?? '', 'Velana') !== false) {
                $gateway['active_gateway'] = 'velana';
            } elseif (stripos($gateway['gateway_name'] ?? '', 'Sigilo') !== false) {
                $gateway['active_gateway'] = 'sigilopay';
            } else {
                $gateway['active_gateway'] = 'payberry';
            }
        }
    }
} catch (Exception $e) {}

// Ler Log de Diagnóstico
$logPath = __DIR__ . '/../gateway_debug.log';
$logContent = file_exists($logPath) ? file_get_contents($logPath) : 'Nenhum log registrado até o momento.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuração de Gateways | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --primary-dark: #002266;
      --danger: #e30613;
      --success: #168019;
      --bg-gray: #f4f6f9;
      --border-color: #e2e8f0;
      --sidebar-width: 240px;
      --payberry-pink: #d10f72;
      --velana-blue: #0052ff;
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

    /* CONTEÚDO PRINCIPAL */
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: calc(100vw - var(--sidebar-width)); }
    .dashboard-header { margin-bottom: 24px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 10px; }
    .active-tag-badge { background: #fdf2f8; color: var(--payberry-pink); font-size: 13px; font-weight: 800; padding: 4px 12px; border-radius: 6px; border: 1px solid #fbcfe8; }
    .active-tag-badge.velana { background: #eff6ff; color: var(--velana-blue); border-color: #bfdbfe; }

    /* SELETOR PRINCIPAL */
    .selector-card { background: #ffffff; border-radius: 12px; border: 2px solid #0052ff; padding: 24px 28px; margin-bottom: 28px; box-shadow: 0 4px 16px rgba(0, 82, 255, 0.08); }
    .selector-title { font-size: 16px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .gw-select-large { width: 100%; height: 54px; padding: 0 16px; border: 2px solid #cbd5e1; border-radius: 10px; font-size: 16px; font-weight: 800; color: #0f172a; outline: none; background: #ffffff; cursor: pointer; transition: all 0.2s; }
    .gw-select-large:focus { border-color: var(--velana-blue); }

    .doc-banner-link { color: #0284c7; font-weight: 800; text-decoration: underline; font-size: 13px; }

    /* SEÇÕES DOS GATEWAYS */
    .gateway-section-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .gateway-section-card.active-gw { border: 2px solid #10b981; background: #ffffff; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12); }
    .gw-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; }
    .gw-card-title { font-size: 18px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px; }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }

    .form-group-gw { margin-bottom: 20px; }
    .form-group-gw label { display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px; }
    .gw-input { width: 100%; height: 48px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; font-family: monospace; }
    .gw-input:focus { border-color: var(--velana-blue); box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.1); }
    .gw-help-txt { font-size: 12px; color: #64748b; margin-top: 6px; display: block; }

    .btn-save-gw { width: 100%; height: 56px; background: var(--velana-blue); color: #ffffff; font-size: 17px; font-weight: 900; border: none; border-radius: 10px; cursor: pointer; box-shadow: 0 4px 14px rgba(0, 82, 255, 0.3); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; margin-bottom: 36px; transition: background 0.2s; }
    .btn-save-gw:hover { background: #0043d4; }

    .log-card { background: #0f172a; color: #38bdf8; border-radius: 12px; padding: 24px; font-family: monospace; font-size: 12px; line-height: 1.5; overflow-x: auto; max-height: 250px; border: 1px solid #1e293b; }
    .log-card-title { font-size: 16px; font-weight: 800; color: #f8fafc; margin-bottom: 12px; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="admin-main-wrapper">
    
    <div class="dashboard-header">
      <h1 class="page-main-title">
        Gerenciador de Gateways 
        <span class="active-tag-badge <?= $gateway['active_gateway'] === 'velana' ? 'velana' : ($gateway['active_gateway'] === 'sigilopay' ? 'sigilopay' : ($gateway['active_gateway'] === 'unipay' ? 'unipay' : ($gateway['active_gateway'] === 'manual' ? 'manual' : ''))) ?>" style="<?= $gateway['active_gateway'] === 'manual' ? 'background: #fef3c7; color: #d97706; border-color: #fde68a;' : ($gateway['active_gateway'] === 'sigilopay' ? 'background: #f3e8ff; color: #7e22ce; border-color: #d8b4fe;' : ($gateway['active_gateway'] === 'unipay' ? 'background: #ecfdf5; color: #059669; border-color: #a7f3d0;' : '')) ?>">
          <?php 
            if ($gateway['active_gateway'] === 'velana') echo '⚡ Velana Ativo no Checkout';
            else if ($gateway['active_gateway'] === 'sigilopay') echo '🛡️ SigiloPay Ativo no Checkout';
            else if ($gateway['active_gateway'] === 'unipay') echo '💎 UniPay BR Ativo no Checkout';
            else if ($gateway['active_gateway'] === 'manual') echo '🔑 Chave Pix Manual Ativa';
            else echo '🍓 Payberry Ativo no Checkout';
          ?>
        </span>
      </h1>
    </div>

    <?php if ($message): ?>
      <div class="alert-success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-danger">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">

      <!-- SELETOR COM SETINHA (SELECIONAR GATEWAY ATIVO) -->
      <div class="selector-card">
        <div class="selector-title">
          <span>🔻 SELECIONE COMO GERAR O PIX NO CHECKOUT:</span>
        </div>
        <select name="active_gateway" id="activeGatewaySelect" class="gw-select-large" onchange="toggleGatewayVisibility()">
          <option value="unipay" <?= ($gateway['active_gateway'] === 'unipay') ? 'selected' : '' ?>>
            💎 UniPay BR Gateway (Automático - app.unipaybr.com)
          </option>
          <option value="sigilopay" <?= ($gateway['active_gateway'] === 'sigilopay') ? 'selected' : '' ?>>
            🛡️ SigiloPay Gateway (Automático)
          </option>
          <option value="velana" <?= ($gateway['active_gateway'] === 'velana') ? 'selected' : '' ?>>
            ⚡ Velana Gateway (Automático)
          </option>
          <option value="payberry" <?= ($gateway['active_gateway'] === 'payberry') ? 'selected' : '' ?>>
            🍓 Payberry Gateway (Automático)
          </option>
          <option value="manual" <?= ($gateway['active_gateway'] === 'manual') ? 'selected' : '' ?>>
            🔑 Chave Pix Manual (CPF, CNPJ, E-mail, Telefone, Chave Aleatória)
          </option>
        </select>
      </div>

      <!-- CARD 0: UNIPAY BR GATEWAY -->
      <div class="gateway-section-card active-gw" id="cardUniPay" style="border-color: #10b981; display: <?= ($gateway['active_gateway'] === 'unipay') ? 'block' : 'none' ?>;">
        <div class="gw-card-header">
          <div class="gw-card-title">
            <span style="font-size: 22px;">💎</span>
            <span>UniPay BR Gateway (Credenciais)</span>
          </div>
          <div style="display: flex; gap: 14px; align-items: center;">
            <a href="http://app.unipaybr.com/auth/register?code=uniopusa" target="_blank" class="doc-banner-link" style="color: #059669; font-weight: 800;">Cadastrar / Criar Conta UniPay ↗</a>
            <span style="color: #cbd5e1;">|</span>
            <a href="http://app.unipaybr.com/auth/login" target="_blank" class="doc-banner-link">Login UniPay ↗</a>
          </div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #166534; line-height: 1.6;">
          <strong>💡 Como obter suas credenciais na UniPay BR:</strong><br>
          1. Acesse ou crie sua conta em: <a href="http://app.unipaybr.com/auth/register?code=uniopusa" target="_blank" style="color: #059669; font-weight: bold; text-decoration: underline;">app.unipaybr.com (Código: uniopusa)</a><br>
          2. No painel da UniPay, acesse <strong>Configurações</strong> &gt; <strong>Integrações / API</strong> (ou Chaves de API)<br>
          3. Copie a sua <strong>Chave Secreta (Secret Key)</strong> e cole no campo abaixo.<br>
          4. Se possuir Chave Pública / Client ID, insira no campo opcional. Se tiver apenas a chave secreta, basta preencher a chave secreta.
        </div>

        <div class="form-group-gw">
          <label>UniPay Secret Key (Chave Secreta / Token de Produção) *</label>
          <input type="password" name="unipay_secret_key" class="gw-input" value="<?= htmlspecialchars($gateway['unipay_secret_key'] ?? '') ?>" placeholder="sk_... ou chave secreta gerada na UniPay">
          <span class="gw-help-txt">Chave secreta de produção gerada no painel da UniPay BR em Integrações / API.</span>
        </div>

        <div class="form-group-gw">
          <label>UniPay Public Key / Client ID (Chave Pública / Opcional)</label>
          <input type="text" name="unipay_public_key" class="gw-input" value="<?= htmlspecialchars($gateway['unipay_public_key'] ?? '') ?>" placeholder="pk_... ou client_id da UniPay (se fornecido)">
          <span class="gw-help-txt">Chave pública ou identificador da sua conta na UniPay BR (opcional caso sua conta utilize apenas Chave Secreta).</span>
        </div>

        <div class="form-group-gw" style="margin-bottom: 0;">
          <label>URL de Webhook (Postback Oficial para colar na UniPay)</label>
          <input type="text" readonly class="gw-input" style="background: #f8fafc; color: #475569;" value="<?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'limegreen-alligator-859678.hostingersite.com') ?>/api/webhook.php">
          <span class="gw-help-txt">Cole esta URL no campo Webhook / Postback dentro do painel da UniPay para confirmação automática de pagamentos no checkout.</span>
        </div>
      </div>

      <!-- CARD 1: PAYBERRY GATEWAY -->
      <div class="gateway-section-card active-gw" id="cardPayberry" style="display: <?= ($gateway['active_gateway'] === 'payberry') ? 'block' : 'none' ?>;">
        <div class="gw-card-header">
          <div class="gw-card-title">
            <span style="font-size: 22px;">🍓</span>
            <span>Payberry Gateway (Credenciais)</span>
          </div>
          <a href="../docs/PAYBERRY_API.md" target="_blank" class="doc-banner-link">Ver Documentação Payberry ↗</a>
        </div>

        <div class="form-group-gw">
          <label>Payberry Secret Key (Chave Secreta)</label>
          <input type="password" name="payberry_secret_key" class="gw-input" value="<?= htmlspecialchars($gateway['payberry_secret_key'] ?? '') ?>" placeholder="sk_live_...">
          <span class="gw-help-txt">Chave secreta oficial de produção da sua conta Payberry.</span>
        </div>

        <div class="form-group-gw">
          <label>Payberry Public Key (Chave Pública)</label>
          <input type="text" name="payberry_public_key" class="gw-input" value="<?= htmlspecialchars($gateway['payberry_public_key'] ?? '') ?>" placeholder="pk_live_...">
          <span class="gw-help-txt">Chave pública oficial de produção da sua conta Payberry.</span>
        </div>
      </div>

      <!-- CARD 2: VELANA GATEWAY -->
      <div class="gateway-section-card active-gw" id="cardVelana" style="display: <?= ($gateway['active_gateway'] === 'velana') ? 'block' : 'none' ?>;">
        <div class="gw-card-header">
          <div class="gw-card-title">
            <span style="font-size: 22px;">⚡</span>
            <span>Velana Gateway (Credenciais)</span>
          </div>
          <a href="https://velana.readme.io/reference/introducao" target="_blank" class="doc-banner-link">Ver Documentação Velana ↗</a>
        </div>

        <div class="form-group-gw">
          <label>Velana Secret Key (Chave Secreta)</label>
          <input type="password" name="velana_secret_key" class="gw-input" value="<?= htmlspecialchars($gateway['velana_secret_key'] ?? '') ?>" placeholder="sk_live_...">
          <span class="gw-help-txt">Chave secreta oficial de produção da sua conta Velana.</span>
        </div>

        <div class="form-group-gw">
          <label>Velana Public Key (Chave Pública / API Key)</label>
          <input type="text" name="velana_api_key" class="gw-input" value="<?= htmlspecialchars($gateway['velana_api_key'] ?? '') ?>" placeholder="pk_live_...">
          <span class="gw-help-txt">Chave pública oficial de produção da sua conta Velana.</span>
        </div>
      </div>

      <!-- CARD 3: SIGILOPAY GATEWAY -->
      <div class="gateway-section-card active-gw" id="cardSigiloPay" style="border-color: #8b5cf6; display: <?= ($gateway['active_gateway'] === 'sigilopay') ? 'block' : 'none' ?>;">
        <div class="gw-card-header">
          <div class="gw-card-title">
            <span style="font-size: 22px;">🛡️</span>
            <span>SigiloPay Gateway (Credenciais)</span>
          </div>
          <a href="https://app.sigilopay.com.br/docs/v1" target="_blank" class="doc-banner-link">Ver Documentação SigiloPay ↗</a>
        </div>

        <div class="form-group-gw">
          <label>SigiloPay Secret Key / Token API (Chave Secreta)</label>
          <input type="password" name="sigilopay_secret_key" class="gw-input" value="<?= htmlspecialchars($gateway['sigilopay_secret_key'] ?? '') ?>" placeholder="Token ou Secret Key da SigiloPay...">
          <span class="gw-help-txt">Chave secreta de produção gerada no painel SigiloPay em Integrações.</span>
        </div>

        <div class="form-group-gw">
          <label>SigiloPay Public Key / Client ID (Chave Pública)</label>
          <input type="text" name="sigilopay_api_key" class="gw-input" value="<?= htmlspecialchars($gateway['sigilopay_api_key'] ?? '') ?>" placeholder="Client ID ou Public Key da SigiloPay...">
          <span class="gw-help-txt">Chave pública ou Client ID gerado no painel SigiloPay.</span>
        </div>
      </div>

      <!-- CARD 4: CHAVE PIX MANUAL -->
      <div class="gateway-section-card active-gw" id="cardManual" style="border-color: #d97706; display: <?= ($gateway['active_gateway'] === 'manual') ? 'block' : 'none' ?>;">
        <div class="gw-card-header">
          <div class="gw-card-title">
            <span style="font-size: 22px;">🔑</span>
            <span>Chave Pix Manual (Gera QR Code & Copia e Cola Oficial BACEN)</span>
          </div>
        </div>

        <div class="form-group-gw">
          <label>Tipo de Chave Pix</label>
          <select name="manual_pix_type" class="gw-input" style="font-family: inherit; font-size: 15px; font-weight: 700;">
            <option value="cpf" <?= (($gateway['manual_pix_type'] ?? 'cpf') === 'cpf') ? 'selected' : '' ?>>CPF (Dígitos ou formatado)</option>
            <option value="cnpj" <?= (($gateway['manual_pix_type'] ?? '') === 'cnpj') ? 'selected' : '' ?>>CNPJ (Dígitos ou formatado)</option>
            <option value="email" <?= (($gateway['manual_pix_type'] ?? '') === 'email') ? 'selected' : '' ?>>E-mail (ex: seuemail@suadominio.com)</option>
            <option value="phone" <?= (($gateway['manual_pix_type'] ?? '') === 'phone') ? 'selected' : '' ?>>Telefone / Celular (ex: 11999999999 ou +5511999999999)</option>
            <option value="random" <?= (($gateway['manual_pix_type'] ?? '') === 'random') ? 'selected' : '' ?>>Chave Aleatória / EVP (ex: a1b2c3d4-e5f6-7890...)</option>
          </select>
          <span class="gw-help-txt">Selecione qual o tipo da sua chave cadastrada no seu banco.</span>
        </div>

        <div class="form-group-gw">
          <label>Sua Chave Pix</label>
          <input type="text" name="manual_pix_key" class="gw-input" value="<?= htmlspecialchars($gateway['manual_pix_key'] ?? '') ?>" placeholder="Digite sua chave Pix (ex: 123.456.789-00, financeiro@loja.com, etc.)">
          <span class="gw-help-txt">Chave Pix para onde o valor de cada pedido gerado será direcionado.</span>
        </div>

        <div class="form-group-gw">
          <label>Nome do Beneficiário / Titular da Conta</label>
          <input type="text" name="manual_pix_name" class="gw-input" value="<?= htmlspecialchars($gateway['manual_pix_name'] ?? 'CASAS BAHIA') ?>" placeholder="ex: CASAS BAHIA">
          <span class="gw-help-txt">Nome exibido ao cliente no aplicativo do banco (máx 25 caracteres, sem acentos).</span>
        </div>

        <div class="form-group-gw">
          <label>Cidade do Beneficiário</label>
          <input type="text" name="manual_pix_city" class="gw-input" value="<?= htmlspecialchars($gateway['manual_pix_city'] ?? 'SAO PAULO') ?>" placeholder="ex: SAO PAULO">
          <span class="gw-help-txt">Cidade cadastrada na conta bancária (máx 15 caracteres, sem acentos).</span>
        </div>
      </div>

      <!-- BOTÃO SALVAR -->
      <button type="submit" class="btn-save-gw">💾 SALVAR CONFIGURAÇÕES DO PIX</button>

    </form>

    <!-- DIAGNÓSTICO DO LOG DA API -->
    <h2 class="log-card-title">📜 Registro de Diagnóstico da API (`gateway_debug.log`)</h2>
    <div class="log-card">
      <?= nl2br(htmlspecialchars($logContent)) ?>
    </div>

  </div>

  <script>
    function toggleGatewayVisibility() {
      const val = document.getElementById("activeGatewaySelect").value;
      const cardUniPay = document.getElementById("cardUniPay");
      const cardPayberry = document.getElementById("cardPayberry");
      const cardVelana = document.getElementById("cardVelana");
      const cardSigiloPay = document.getElementById("cardSigiloPay");
      const cardManual = document.getElementById("cardManual");

      if (cardUniPay) cardUniPay.style.display = (val === "unipay") ? "block" : "none";
      if (cardPayberry) cardPayberry.style.display = (val === "payberry") ? "block" : "none";
      if (cardVelana) cardVelana.style.display = (val === "velana") ? "block" : "none";
      if (cardSigiloPay) cardSigiloPay.style.display = (val === "sigilopay") ? "block" : "none";
      if (cardManual) cardManual.style.display = (val === "manual") ? "block" : "none";
    }
  </script>

</body>
</html>
