<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';

// Criar pasta de uploads caso não exista
$uploadDir = __DIR__ . '/../assets/images/banners/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// Ações POST (Adicionar Banner / Excluir Banner / Atualizar Categoria)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "O arquivo do GIF é maior do que o limite de upload do seu servidor (" . ini_get('post_max_size') . "). Por favor, use um GIF menor ou cole o Link/URL direto do GIF no campo de texto.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $imageUrl = trim($_POST['image_url'] ?? '');
            if (!empty($imageUrl) && !preg_match('/^https?:\/\//i', $imageUrl) && strpos($imageUrl, 'assets/') !== 0) {
                $imageUrl = 'https://' . $imageUrl;
            }
            $linkUrl = trim($_POST['link_url'] ?? '#');
            $sortOrder = intval($_POST['sort_order'] ?? 1);

            // Processar Upload de Arquivo
            if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $fileErr = $_FILES['banner_file']['error'];
                if ($fileErr === UPLOAD_ERR_OK) {
                    $fileTmp = $_FILES['banner_file']['tmp_name'];
                    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $_FILES['banner_file']['name']);
                    $targetPath = $uploadDir . $fileName;

                    if (@move_uploaded_file($fileTmp, $targetPath)) {
                        $imageUrl = 'assets/images/banners/' . $fileName;
                    } else {
                        // Ambiente Serverless (Vercel) com disco somente-leitura: armazena como Data URI Base64
                        $fileData = @file_get_contents($fileTmp);
                        if ($fileData !== false && strlen($fileData) > 0) {
                            $mime = 'image/jpeg';
                            if (function_exists('mime_content_type')) {
                                $detected = @mime_content_type($fileTmp);
                                if ($detected) $mime = $detected;
                            }
                            $imageUrl = 'data:' . $mime . ';base64,' . base64_encode($fileData);
                        } else {
                            $error = "Não foi possível salvar o arquivo na pasta de destino. Tente usar a URL direta da imagem/GIF.";
                        }
                    }
                } else if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
                    $error = "O arquivo do GIF/Imagem selecionado é muito grande para o servidor. Tente usar um arquivo menor ou cole o link direto (URL).";
                } else {
                    $error = "Erro no upload do arquivo (Código: $fileErr). Tente novamente ou use a URL direta da imagem.";
                }
            }

            if (empty($error)) {
                if (!empty($imageUrl)) {
                    try {
                        $stmt = $db->prepare("INSERT INTO home_banners (image_url, link_url, sort_order) VALUES (:img, :link, :sort)");
                        $stmt->execute([
                            'img' => $imageUrl,
                            'link' => $linkUrl,
                            'sort' => $sortOrder
                        ]);
                        $message = "Novo banner (imagem/GIF) cadastrado com sucesso!";
                    } catch (Exception $e) {
                        $error = "Erro ao cadastrar banner no banco de dados: " . $e->getMessage();
                    }
                } else {
                    $error = "Por favor, selecione uma imagem/GIF para upload ou insira o link direto (URL).";
                }
            }
        } else if ($action === 'delete') {
            $bannerId = intval($_POST['banner_id'] ?? 0);
            if ($bannerId > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM home_banners WHERE id = :id");
                    $stmt->execute(['id' => $bannerId]);
                    $message = "Banner excluído com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao excluir banner: " . $e->getMessage();
                }
            }
        } else if ($action === 'update_category') {
            $catId = intval($_POST['cat_id'] ?? 0);
            $catUrl = trim($_POST['cat_image_url'] ?? '');
            if (!empty($catUrl) && !preg_match('/^https?:\/\//i', $catUrl) && strpos($catUrl, 'assets/') !== 0 && strpos($catUrl, '/') !== 0 && strpos($catUrl, 'data:') !== 0) {
                $catUrl = 'https://' . $catUrl;
            }

            if (isset($_FILES['cat_file']) && $_FILES['cat_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $fileErr = $_FILES['cat_file']['error'];
                if ($fileErr === UPLOAD_ERR_OK) {
                    $fileTmp = $_FILES['cat_file']['tmp_name'];
                    $fileName = 'cat_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $_FILES['cat_file']['name']);
                    $targetPath = $uploadDir . $fileName;

                    if (@move_uploaded_file($fileTmp, $targetPath)) {
                        $catUrl = 'assets/images/banners/' . $fileName;
                    } else {
                        // Ambiente Serverless (Vercel) com disco somente-leitura: armazena como Data URI Base64
                        $fileData = @file_get_contents($fileTmp);
                        if ($fileData !== false && strlen($fileData) > 0) {
                            $mime = 'image/gif';
                            if (function_exists('mime_content_type')) {
                                $detected = @mime_content_type($fileTmp);
                                if ($detected) $mime = $detected;
                            }
                            $catUrl = 'data:' . $mime . ';base64,' . base64_encode($fileData);
                        } else {
                            $error = "Não foi possível salvar o GIF na pasta de destino. Tente utilizar a URL direta do GIF.";
                        }
                    }
                } else if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
                    $error = "O arquivo do GIF é maior do que o limite de upload do servidor (" . ini_get('upload_max_filesize') . "). Cole o link (URL) do GIF ou use um arquivo menor.";
                } else {
                    $error = "Erro no upload do GIF (Código: $fileErr). Tente utilizar a URL direta do GIF.";
                }
            }

            if (empty($error) && $catId > 0) {
                try {
                    $stmt = $db->prepare("UPDATE home_categories SET image_url = :url WHERE id = :id");
                    $stmt->execute(['url' => $catUrl, 'id' => $catId]);
                    $message = "Ícone da Categoria / GIF atualizado com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao atualizar categoria: " . $e->getMessage();
                }
            }
        }
    }
}

// Carregar Banners Cadastrados
$banners = [];
try {
    $stmt = $db->query("SELECT * FROM home_banners ORDER BY sort_order ASC, id ASC");
    $banners = $stmt->fetchAll();
} catch (Exception $e) {}

// Carregar Categorias da Home
$categories = [];
try {
    $stmtC = $db->query("SELECT * FROM home_categories ORDER BY sort_order ASC");
    $categories = $stmtC->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Banners e Ícones da Home | Painel Admin Casas Bahia</title>
  <style>
    :root {
      --primary: #003399;
      --danger: #e30613;
      --success: #168019;
      --bg-gray: #f4f6f9;
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

    /* MAIN CONTENT */
    .admin-main-wrapper { margin-left: var(--sidebar-width); flex: 1; padding: 32px 36px; max-width: calc(100vw - var(--sidebar-width)); }
    .dashboard-header { margin-bottom: 24px; }
    .page-main-title { font-size: 26px; font-weight: 900; color: #1e293b; display: flex; align-items: center; gap: 10px; }

    .card-box { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 28px; margin-bottom: 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .card-box-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 18px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }

    .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }
    .alert-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-bottom: 24px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .form-control { width: 100%; height: 46px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; }
    .form-control:focus { border-color: var(--primary); }

    .btn-submit { background: var(--primary); color: #ffffff; font-size: 15px; font-weight: 800; padding: 14px 24px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 51, 153, 0.2); }
    .btn-submit:hover { background: #002266; }

    .dots-preview-bar { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 16px 0 24px 0; background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #e2e8f0; }
    .dot-item { width: 12px; height: 12px; border-radius: 50%; background: #e2e8f0; transition: all 0.2s; }
    .dot-item.active { background: #003399; transform: scale(1.2); }

    .banners-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .banners-table th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 13px; font-weight: 800; border-bottom: 1px solid #e2e8f0; }
    .banners-table td { padding: 16px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .banner-thumb { width: 160px; height: 70px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; }
    .cat-thumb { width: 54px; height: 54px; border-radius: 16px; object-fit: cover; background: #003399; }
    
    .btn-del { background: #ef4444; color: #ffffff; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 800; font-size: 12px; cursor: pointer; }
    .btn-del:hover { background: #dc2626; }
    
    .cat-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 14px; }
    .cat-card-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; }
    .cat-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
  </style>
</head>
<body>

  <!-- BARRA LATERAL (SIDEBAR) -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="admin-main-wrapper">

    <div class="dashboard-header">
      <h1 class="page-main-title">
        🎠 Banners & Ícones das Categorias da Home
      </h1>
    </div>

    <?php if ($message): ?>
      <div class="alert-success">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-danger">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- SEÇÃO 1: GERENCIAR ÍCONE DA CATEGORIA "INVASÃO DE OFERTAS / USE: DESCONTO" E OUTROS -->
    <div class="card-box" style="border-top: 4px solid #003399;">
      <h2 class="card-box-title">⚡ Ícones das Categorias do Carrossel (Suporta GIFs Animados e Imagens)</h2>
      <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
        Você pode alterar a foto ou enviar um **GIF animado** para a categoria <strong>INVASÃO DE OFERTAS (USE: DESCONTO)</strong> e todas as outras abaixo:
      </p>

      <div class="cat-grid-admin">
        <?php foreach ($categories as $cat): ?>
          <div class="cat-card-item">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_category">
              <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">

              <div class="cat-card-header">
                <?php if (!empty($cat['image_url'])): ?>
                  <?php 
                    $displayCatUrl = (strpos($cat['image_url'], 'http') === 0 || strpos($cat['image_url'], '/') === 0 || strpos($cat['image_url'], 'data:') === 0) ? $cat['image_url'] : '../' . $cat['image_url'];
                    $catExt = strtolower(pathinfo(parse_url($displayCatUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                  ?>
                  <?php if (in_array($catExt, ['mp4', 'webm', 'mov'])): ?>
                    <video src="<?= htmlspecialchars($displayCatUrl) ?>" autoplay loop muted playsinline class="cat-thumb" style="object-fit: cover;"></video>
                  <?php else: ?>
                    <img src="<?= htmlspecialchars($displayCatUrl) ?>" class="cat-thumb" alt="Ícone">
                  <?php endif; ?>
                <?php else: ?>
                  <div class="cat-thumb" style="display:flex; align-items:center; justify-content:center; color:#fff; font-size:10px; font-weight:900; text-align:center;">SEM FOTO</div>
                <?php endif; ?>

                <div>
                  <div style="font-size: 15px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($cat['title']) ?></div>
                  <div style="font-size: 11px; color: #64748b; font-family: monospace;">Slug: <?= htmlspecialchars($cat['slug']) ?></div>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 10px;">
                <label style="font-size: 12px;">Upload de Imagem, GIF ou Vídeo (.gif, .png, .jpg, .mp4)</label>
                <input type="file" name="cat_file" accept="image/*,video/*" class="form-control" style="font-size: 12px; height: 40px; padding-top: 6px;">
              </div>

              <div class="form-group" style="margin-bottom: 12px;">
                <label style="font-size: 12px;">OU Link Direto da Imagem/GIF (URL)</label>
                <input type="text" name="cat_image_url" class="form-control" style="font-size: 12px; height: 40px;" value="<?= htmlspecialchars($cat['image_url']) ?>" placeholder="https://site.com/animacao.gif">
              </div>

              <button type="submit" class="btn-submit" style="padding: 10px 16px; font-size: 13px; width: 100%;">💾 Salvar GIF/Imagem de <?= htmlspecialchars($cat['title']) ?></button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- SEÇÃO 2: FORMULÁRIO DE NOVO BANNER DO CARROSSEL HERO -->
    <div class="card-box">
      <h2 class="card-box-title">➕ Adicionar Novo Banner ao Carrossel Hero Principal</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
          <label>Upload da Imagem, GIF Animado ou Vídeo (.gif, .png, .jpg, .webp, .mp4)</label>
          <input type="file" name="banner_file" accept="image/*,video/*" class="form-control" style="padding-top: 10px;">
          <span style="font-size: 12px; color: #64748b; margin-top: 4px; display: block;">Suporta imagens estáticas (PNG, JPG, WEBP), **GIFs Animados (.gif)** e vídeos em loop (.mp4).</span>
        </div>

        <div class="form-group">
          <label>OU Link Direto da Imagem ou GIF (URL Externa)</label>
          <input type="text" name="image_url" class="form-control" placeholder="https://site.com/banner_animado.gif ou cole o link do GIF">
        </div>

        <div class="form-group">
          <label>Ordem de Exibição (1, 2, 3...)</label>
          <input type="number" name="sort_order" class="form-control" value="<?= count($banners) + 1 ?>" min="1" max="20">
        </div>

        <button type="submit" class="btn-submit">💾 SALVAR E ADICIONAR BANNER</button>
      </form>
    </div>

    <!-- SEÇÃO 3: LISTA DE BANNERS HERO -->
    <div class="card-box">
      <h2 class="card-box-title">📱 Banners do Carrossel Hero (<?= count($banners) ?> Slides)</h2>
      
      <div class="dots-preview-bar">
        <?php 
          $totalDots = max(count($banners), 1);
          for ($i = 0; $i < $totalDots; $i++): 
        ?>
          <div class="dot-item <?= ($i === 0) ? 'active' : '' ?>"></div>
        <?php endfor; ?>
      </div>

      <table class="banners-table">
        <thead>
          <tr>
            <th>Ordem</th>
            <th>Imagem / GIF / Vídeo do Banner</th>
            <th>Caminho / URL</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($banners)): ?>
            <tr>
              <td colspan="4" style="text-align: center; color: #94a3b8; padding: 24px;">
                Nenhum banner personalizado cadastrado. A home usará o banner padrão promocional.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($banners as $idx => $b): ?>
              <?php 
                $displayBannerUrl = (strpos($b['image_url'], 'http') === 0 || strpos($b['image_url'], '/') === 0 || strpos($b['image_url'], 'data:') === 0) ? $b['image_url'] : '../' . $b['image_url'];
                $bExt = strtolower(pathinfo(parse_url($displayBannerUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
              ?>
              <tr>
                <td><strong>#<?= $b['sort_order'] ?></strong></td>
                <td>
                  <?php if (in_array($bExt, ['mp4', 'webm', 'mov'])): ?>
                    <video src="<?= htmlspecialchars($displayBannerUrl) ?>" autoplay loop muted playsinline class="banner-thumb" style="object-fit: cover;"></video>
                  <?php else: ?>
                    <img src="<?= htmlspecialchars($displayBannerUrl) ?>" class="banner-thumb" alt="Banner">
                  <?php endif; ?>
                </td>
                <td style="font-family: monospace; font-size: 12px; color: #64748b;">
                  <?= htmlspecialchars($b['image_url']) ?>
                </td>
                <td>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente remover este banner?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn-del">🗑️ Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</body>
</html>
