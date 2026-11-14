<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$products = [];
$error = '';
$success = $_GET['msg'] ?? '';

try {
    $stmt = $db->query("
        SELECT p.*, 
               (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_main DESC, sort_order ASC LIMIT 1) as main_image
        FROM products p 
        ORDER BY p.id DESC
    ");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Erro ao buscar produtos: " . $e->getMessage();
}

$accessSettings = ['param_name' => 'src', 'master_token' => 'promo', 'is_enabled' => 1];
try {
    $stmtS = $db->query("SELECT * FROM access_control_settings WHERE id = 1 LIMIT 1");
    $dbSettings = $stmtS->fetch();
    if ($dbSettings) {
        $accessSettings = array_merge($accessSettings, $dbSettings);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Produtos | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --bg-gray: #f4f6f9;
      --sidebar-width: 240px;
      --success: #168019;
      --danger: #e30613;
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
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: calc(100vw - var(--sidebar-width)); }
    .dashboard-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; }

    .btn-add { background: var(--success); color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }

    .section-card-table { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); overflow: hidden; }
    
    .prod-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .prod-table th { background: #f8fafc; padding: 14px 18px; border-bottom: 2px solid #e2e8f0; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .prod-table td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .prod-table tr:hover { background: #f8fafc; }

    .prod-thumb { width: 50px; height: 50px; object-fit: contain; border-radius: 6px; border: 1px solid #e2e8f0; padding: 2px; }
    
    .action-links { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .btn-copy { background: #0284c7; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s ease; }
    .btn-copy:hover { background: #0369a1; }
    .btn-edit { background: var(--primary); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; }
    .btn-delete { background: var(--danger); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; }
    .btn-view { background: #64748b; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL DE PRODUTOS -->
  <div class="admin-main-wrapper">
    
    <div class="dashboard-header">
      <h1 class="page-main-title">Catálogo de Produtos</h1>
      <div style="display: flex; gap: 10px;">
        <a href="import_product.php" class="btn-add" style="background-color: var(--primary);">📥 Importar da Casas Bahia</a>
        <a href="product_form.php" class="btn-add">➕ Novo Produto</a>
      </div>
    </div>

    <div class="section-card-table">
      <table class="prod-table">
        <thead>
          <tr>
            <th>Imagem</th>
            <th>Produto / Título</th>
            <th>Marca</th>
            <th>SKU / Código</th>
            <th>Preço Cartão</th>
            <th>Preço Pix</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <img src="<?= htmlspecialchars($p['main_image'] ?: '../assets/images/product_aoc_main.png') ?>" class="prod-thumb" alt="Foto">
              </td>
              <td>
                <strong style="color: #1e293b;"><?= htmlspecialchars($p['title']) ?></strong>
              </td>
              <td><span style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($p['brand']) ?></span></td>
              <td><span style="font-family: monospace; color: #64748b;"><?= htmlspecialchars($p['item_code']) ?></span></td>
              <td><strong>R$ <?= number_format($p['regular_price'], 2, ',', '.') ?></strong></td>
              <td><strong style="color: var(--success);">R$ <?= number_format($p['pix_price'], 2, ',', '.') ?></strong></td>
              <td>
                <div class="action-links">
                  <button type="button" onclick="copyProductLink(this, '<?= $p['id'] ?>')" class="btn-copy" title="Copiar link seguro do produto">📋 Copiar Link</button>
                  <a href="../index.php?id=<?= $p['id'] ?>&<?= urlencode($accessSettings['param_name']) ?>=<?= urlencode($accessSettings['master_token']) ?>" target="_blank" class="btn-view">Ver</a>
                  <a href="product_form.php?id=<?= $p['id'] ?>" class="btn-edit">Editar</a>
                  <a href="product_delete.php?id=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

  <script>
    const paramName = "<?= addslashes($accessSettings['param_name']) ?>";
    const masterToken = "<?= addslashes($accessSettings['master_token']) ?>";
    const isEnabled = <?= $accessSettings['is_enabled'] ? 'true' : 'false' ?>;

    function copyProductLink(btn, productId) {
      const basePath = window.location.origin + window.location.pathname.replace(/\/admin\/.*$/, '');
      let link = basePath + '/index.php?id=' + productId;
      if (isEnabled && masterToken) {
        link += '&' + encodeURIComponent(paramName) + '=' + encodeURIComponent(masterToken);
      }
      
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link).then(function() {
          showCopiedState(btn);
        }).catch(function() {
          fallbackCopy(link, btn);
        });
      } else {
        fallbackCopy(link, btn);
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
        btn.style.backgroundColor = '#0284c7';
      }, 2000);
    }
  </script>

</body>
</html>
