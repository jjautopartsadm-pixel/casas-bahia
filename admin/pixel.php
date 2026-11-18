<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pixelId = trim($_POST['pixel_id'] ?? '');
    $accessToken = trim($_POST['access_token'] ?? '');
    $testEventCode = trim($_POST['test_event_code'] ?? '');
    $activeStatus = isset($_POST['active_status']) ? 1 : 0;

    try {
        $check = $db->query("SELECT id FROM pixel_settings WHERE id = 1")->fetch();
        if ($check) {
            $stmt = $db->prepare("
                UPDATE pixel_settings SET 
                    pixel_id = :pid,
                    access_token = :token,
                    test_event_code = :test_code,
                    active_status = :status,
                    updated_at = :up_at
                WHERE id = 1
            ");
        } else {
            $stmt = $db->prepare("
                INSERT INTO pixel_settings (id, pixel_id, access_token, test_event_code, active_status, updated_at)
                VALUES (1, :pid, :token, :test_code, :status, :up_at)
            ");
        }
        $stmt->execute([
            'pid' => $pixelId,
            'token' => $accessToken,
            'test_code' => $testEventCode,
            'status' => $activeStatus,
            'up_at' => date('Y-m-d H:i:s')
        ]);
        $message = "Configurações do Facebook Pixel & CAPI salvas com sucesso!";
    } catch (Exception $e) {
        $error = "Erro ao salvar Pixel: " . $e->getMessage();
    }
}

// Carregar configurações atuais
$pixel = [
    'pixel_id' => '',
    'access_token' => '',
    'test_event_code' => '',
    'active_status' => 1
];

try {
    $stmt = $db->query("SELECT * FROM pixel_settings WHERE id = 1 LIMIT 1");
    $dbPixel = $stmt->fetch();
    if ($dbPixel) {
        $pixel = $dbPixel;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pixel Facebook | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --bg-gray: #f4f6f9;
      --sidebar-width: 240px;
      --success: #168019;
      --fb-blue: #1877f2;
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
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: 800px; }
    .dashboard-header { margin-bottom: 24px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 10px; }
    .fb-tag-badge { background: #e7f3ff; color: var(--fb-blue); font-size: 13px; font-weight: 800; padding: 4px 10px; border-radius: 6px; border: 1px solid #99c2ff; }

    .doc-banner-card { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 18px 20px; margin-bottom: 28px; font-size: 14px; color: #3730a3; line-height: 1.5; }
    .doc-banner-card strong { color: #312e81; }

    .pixel-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    
    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }

    .form-group-px { margin-bottom: 22px; }
    .form-group-px label { display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px; }
    .px-input { width: 100%; height: 50px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; color: #0f172a; outline: none; background: #ffffff; }
    .px-input:focus { border-color: var(--fb-blue); box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1); }
    .px-help-txt { font-size: 12px; color: #64748b; margin-top: 6px; display: block; }

    .checkbox-toggle-row { display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 700; color: #1e293b; margin-top: 10px; margin-bottom: 28px; }
    .checkbox-toggle-row input { width: 20px; height: 20px; accent-color: var(--fb-blue); }

    .btn-save-px { width: 100%; height: 52px; background: var(--fb-blue); color: #ffffff; font-size: 16px; font-weight: 800; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(24, 119, 242, 0.25); text-transform: uppercase; letter-spacing: 0.5px; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL DE PIXEL FACEBOOK -->
  <div class="admin-main-wrapper">
    
    <div class="dashboard-header">
      <h1 class="page-main-title">
        Facebook Pixel & CAPI
        <span class="fb-tag-badge">Rastreamento 100% Meta</span>
      </h1>
    </div>

    <!-- BANNER EXPLICATIVO -->
    <div class="doc-banner-card">
      <div><strong>Rastreamento Híbrido Completo (Pixel + Conversions API CAPI):</strong> Envia os eventos via navegador (front-end) e via servidor (back-end), garantindo 100% de precisão no rastreamento de PageView, InitiateCheckout e Purchase mesmo com iOS 14+ ou bloqueadores de anúncios.</div>
    </div>

    <?php if ($message): ?>
      <div class="alert-success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-danger">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="pixel-card">
      <form method="POST" action="">

        <div class="form-group-px">
          <label>ID do Pixel do Facebook (Pixel ID)</label>
          <input type="text" name="pixel_id" class="px-input" value="<?= htmlspecialchars($pixel['pixel_id'] ?? '') ?>" placeholder="ex: 123456789012345" required>
          <span class="px-help-txt">Localizado no Gerenciador de Eventos da Meta / Facebook.</span>
        </div>

        <div class="form-group-px">
          <label>Token de Acesso da API de Conversões (CAPI Token)</label>
          <textarea name="access_token" class="px-input" style="height: 90px; padding: 12px; font-family: monospace; font-size: 13px;" placeholder="EAAGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."><?= htmlspecialchars($pixel['access_token'] ?? '') ?></textarea>
          <span class="px-help-txt">Token gerado em Gerenciador de Eventos > Configurações > Gerar Token de Acesso.</span>
        </div>

        <div class="form-group-px">
          <label>Código de Teste do Servidor (Opcional - Test Event Code)</label>
          <input type="text" name="test_event_code" class="px-input" value="<?= htmlspecialchars($pixel['test_event_code'] ?? '') ?>" placeholder="ex: TEST12345 (deixe em branco em produção)">
          <span class="px-help-txt">Encontrado na aba "Testar Eventos" do Gerenciador de Eventos para depuração em tempo real.</span>
        </div>

        <div class="checkbox-toggle-row">
          <input type="checkbox" name="active_status" id="activeStatus" <?= (!empty($pixel['active_status'])) ? 'checked' : '' ?>>
          <label for="activeStatus">Ativar rastreamento 100% Facebook Pixel & Conversions API</label>
        </div>

        <button type="submit" class="btn-save-px">Salvar Configurações do Pixel</button>
      </form>
    </div>

  </div>

</body>
</html>
