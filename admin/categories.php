<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$message = '';
$error = '';

$uploadDir = __DIR__ . '/../assets/images/banners/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 1);

        if (!empty($imageUrl) && !preg_match('/^https?:\/\//i', $imageUrl) && strpos($imageUrl, 'assets/') !== 0) {
            $imageUrl = 'https://' . $imageUrl;
        }

        if (isset($_FILES['cat_file']) && $_FILES['cat_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileErr = $_FILES['cat_file']['error'];
            if ($fileErr === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['cat_file']['tmp_name'];
                $fileName = 'cat_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $_FILES['cat_file']['name']);
                $targetPath = $uploadDir . $fileName;

                if (@move_uploaded_file($fileTmp, $targetPath)) {
                    $imageUrl = 'assets/images/banners/' . $fileName;
                } else {
                    $fileData = @file_get_contents($fileTmp);
                    if ($fileData !== false && strlen($fileData) > 0) {
                        $fileExt = strtolower(pathinfo($_FILES['cat_file']['name'], PATHINFO_EXTENSION));
                        $mime = 'image/gif';
                        if (in_array($fileExt, ['mp4', 'webm', 'mov', 'm4v'])) {
                            $mime = 'video/' . ($fileExt === 'mov' ? 'mp4' : $fileExt);
                        } elseif ($fileExt === 'png') {
                            $mime = 'image/png';
                        } elseif (in_array($fileExt, ['jpg', 'jpeg'])) {
                            $mime = 'image/jpeg';
                        } elseif ($fileExt === 'webp') {
                            $mime = 'image/webp';
                        } elseif (function_exists('mime_content_type')) {
                            $detected = @mime_content_type($fileTmp);
                            if ($detected) $mime = $detected;
                        }
                        $imageUrl = 'data:' . $mime . ';base64,' . base64_encode($fileData);
                    } else {
                        $error = "Não foi possível salvar o arquivo na pasta de destino.";
                    }
                }
            }
        }

        if (empty($error)) {
            if (!empty($title)) {
                $slug = preg_replace('/[^a-z0-9]/', '', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $title)));
                if (empty($slug)) { $slug = 'cat_' . time(); }
                try {
                    $stmt = $db->prepare("INSERT INTO home_categories (slug, title, image_url, sort_order) VALUES (:slug, :title, :img, :sort)");
                    $stmt->execute([
                        'slug' => $slug,
                        'title' => $title,
                        'img' => $imageUrl,
                        'sort' => $sortOrder
                    ]);
                    $message = "Nova categoria '$title' cadastrada com sucesso!";
                } catch (Exception $e) {
                    $error = "Erro ao cadastrar categoria: " . $e->getMessage();
                }
            } else {
                $error = "Por favor, insira o nome da categoria.";
            }
        }
    } else if ($action === 'update') {
        $catId = intval($_POST['cat_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 1);

        if (!empty($imageUrl) && !preg_match('/^https?:\/\//i', $imageUrl) && strpos($imageUrl, 'assets/') !== 0 && strpos($imageUrl, '/') !== 0 && strpos($imageUrl, 'data:') !== 0) {
            $imageUrl = 'https://' . $imageUrl;
        }

        if (isset($_FILES['cat_file']) && $_FILES['cat_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileErr = $_FILES['cat_file']['error'];
            if ($fileErr === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['cat_file']['tmp_name'];
                $fileName = 'cat_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $_FILES['cat_file']['name']);
                $targetPath = $uploadDir . $fileName;

                if (@move_uploaded_file($fileTmp, $targetPath)) {
                    $imageUrl = 'assets/images/banners/' . $fileName;
                } else {
                    $fileData = @file_get_contents($fileTmp);
                    if ($fileData !== false && strlen($fileData) > 0) {
                        $fileExt = strtolower(pathinfo($_FILES['cat_file']['name'], PATHINFO_EXTENSION));
                        $mime = 'image/gif';
                        if (in_array($fileExt, ['mp4', 'webm', 'mov', 'm4v'])) {
                            $mime = 'video/' . ($fileExt === 'mov' ? 'mp4' : $fileExt);
                        } elseif ($fileExt === 'png') {
                            $mime = 'image/png';
                        } elseif (in_array($fileExt, ['jpg', 'jpeg'])) {
                            $mime = 'image/jpeg';
                        } elseif ($fileExt === 'webp') {
                            $mime = 'image/webp';
                        } elseif (function_exists('mime_content_type')) {
                            $detected = @mime_content_type($fileTmp);
                            if ($detected) $mime = $detected;
                        }
                        $imageUrl = 'data:' . $mime . ';base64,' . base64_encode($fileData);
                    }
                }
            }
        }

        if ($catId > 0 && !empty($title)) {
            try {
                $stmt = $db->prepare("UPDATE home_categories SET title = :title, image_url = :url, sort_order = :sort WHERE id = :id");
                $stmt->execute(['title' => $title, 'url' => $imageUrl, 'sort' => $sortOrder, 'id' => $catId]);
                $message = "Categoria '$title' atualizada com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao atualizar categoria: " . $e->getMessage();
            }
        }
    } else if ($action === 'delete') {
        $catId = intval($_POST['cat_id'] ?? 0);
        if ($catId > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM home_categories WHERE id = :id");
                $stmt->execute(['id' => $catId]);
                $message = "Categoria excluída com sucesso!";
            } catch (Exception $e) {
                $error = "Erro ao excluir categoria: " . $e->getMessage();
            }
        }
    } else if ($action === 'move_up' || $action === 'move_down') {
        $catId = intval($_POST['cat_id'] ?? 0);
        $currentSort = intval($_POST['sort_order'] ?? 0);

        if ($catId > 0) {
            if ($action === 'move_up') {
                $stmtAdj = $db->prepare("SELECT id, sort_order FROM home_categories WHERE sort_order < :sort ORDER BY sort_order DESC LIMIT 1");
            } else {
                $stmtAdj = $db->prepare("SELECT id, sort_order FROM home_categories WHERE sort_order > :sort ORDER BY sort_order ASC LIMIT 1");
            }
            $stmtAdj->execute(['sort' => $currentSort]);
            $adj = $stmtAdj->fetch();

            if ($adj) {
                $db->prepare("UPDATE home_categories SET sort_order = :sort WHERE id = :id")->execute(['sort' => $adj['sort_order'], 'id' => $catId]);
                $db->prepare("UPDATE home_categories SET sort_order = :sort WHERE id = :id")->execute(['sort' => $currentSort, 'id' => $adj['id']]);
                $message = "Ordem das categorias alterada!";
            }
        }
    }
}

$categories = [];
try {
    $stmtC = $db->query("SELECT * FROM home_categories ORDER BY sort_order ASC, id ASC");
    $categories = $stmtC->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorias da Home | Painel Admin</title>
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
    .cat-thumb { width: 54px; height: 54px; border-radius: 16px; object-fit: cover; background: #0a0a0a; }
    .cat-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; margin-top: 14px; }
    .cat-card-item { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px; position: relative; }
    .cat-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
    .btn-del { background: #ef4444; color: #ffffff; border: none; padding: 8px 12px; border-radius: 6px; font-weight: 800; font-size: 12px; cursor: pointer; }
    .btn-move { background: #e2e8f0; color: #1e293b; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 800; font-size: 13px; cursor: pointer; }
    .btn-move:hover { background: #cbd5e1; }
  </style>
</head>
<body>

  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="admin-main-wrapper">
    <div class="dashboard-header">
      <h1 class="page-main-title">🏷️ Gerenciar Categorias da Home (+ Categorias)</h1>
    </div>

    <?php if ($message): ?><div class="alert-success">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger">✕ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- 1. FORMULÁRIO: ADICIONAR NOVA CATEGORIA -->
    <div class="card-box" style="border-top: 4px solid var(--primary);">
      <h2 class="card-box-title">➕ Adicionar Nova Categoria (+ Categoria)</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label>Nome / Título da Categoria</label>
            <input type="text" name="title" class="form-control" placeholder="Ex: TV's, iPhone, Notebooks..." required>
          </div>

          <div class="form-group">
            <label>Ordem de Exibição</label>
            <input type="number" name="sort_order" class="form-control" value="<?= count($categories) + 1 ?>" min="1">
          </div>
        </div>

        <div class="form-group">
          <label>Upload da Imagem, GIF Animado ou Vídeo (.png, .jpg, .gif, .mp4)</label>
          <input type="file" name="cat_file" accept="image/*,video/*" class="form-control" style="padding-top: 8px;">
        </div>

        <div class="form-group">
          <label>OU Link Direto (URL)</label>
          <input type="text" name="image_url" class="form-control" placeholder="https://site.com/icone.jpg ou assets/images/cat_tvs.jpg">
        </div>

        <button type="submit" class="btn-submit">💾 SALVAR NOVA CATEGORIA</button>
      </form>
    </div>

    <!-- 2. LISTA E ORGANIZAÇÃO DE CATEGORIAS -->
    <div class="card-box">
      <h2 class="card-box-title">📱 Categorias Cadastradas (Organizar / Subir / Descer)</h2>
      <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
        Altere o nome, a foto ou use as setas <strong>⬆️ Subir</strong> e <strong>⬇️ Descer</strong> para organizar a ordem de exibição no carrossel da home.
      </p>

      <div class="cat-grid-admin">
        <?php foreach ($categories as $index => $cat): ?>
          <div class="cat-card-item">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">

              <div class="cat-card-header">
                <?php if (!empty($cat['image_url'])): ?>
                  <?php 
                    $displayCatUrl = (strpos($cat['image_url'], 'http') === 0 || strpos($cat['image_url'], '/') === 0 || strpos($cat['image_url'], 'data:') === 0) ? $cat['image_url'] : '../' . $cat['image_url'];
                  ?>
                  <?php if (isVideoMedia($displayCatUrl)): ?>
                    <video src="<?= htmlspecialchars($displayCatUrl) ?>" autoplay loop muted playsinline class="cat-thumb" style="object-fit: cover;"></video>
                  <?php else: ?>
                    <img src="<?= htmlspecialchars($displayCatUrl) ?>" class="cat-thumb" alt="Ícone">
                  <?php endif; ?>
                <?php else: ?>
                  <div class="cat-thumb" style="display:flex; align-items:center; justify-content:center; color:#fff; font-size:9px; font-weight:900; text-align:center;">SEM FOTO</div>
                <?php endif; ?>

                <div style="flex: 1;">
                  <span style="font-size: 11px; background: #003399; color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: 700;">#<?= $cat['sort_order'] ?></span>
                  <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;"><?= htmlspecialchars($cat['title']) ?></div>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 10px;">
                <label style="font-size: 12px;">Nome da Categoria</label>
                <input type="text" name="title" value="<?= htmlspecialchars($cat['title']) ?>" class="form-control" style="font-size: 13px; height: 38px;">
              </div>

              <div class="form-group" style="margin-bottom: 10px;">
                <label style="font-size: 12px;">Upload de Imagem/GIF</label>
                <input type="file" name="cat_file" accept="image/*,video/*" class="form-control" style="font-size: 11px; height: 36px; padding-top: 4px;">
              </div>

              <div class="form-group" style="margin-bottom: 12px;">
                <label style="font-size: 12px;">URL da Foto</label>
                <input type="text" name="image_url" value="<?= htmlspecialchars($cat['image_url']) ?>" class="form-control" style="font-size: 12px; height: 36px;">
              </div>

              <input type="hidden" name="sort_order" value="<?= $cat['sort_order'] ?>">

              <div style="display: flex; gap: 8px; margin-top: 12px; align-items: center;">
                <button type="submit" class="btn-submit" style="padding: 8px 12px; font-size: 12px; flex: 1;">💾 Salvar</button>
              </form>

              <!-- Botões Subir / Descer -->
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="move_up">
                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                <input type="hidden" name="sort_order" value="<?= $cat['sort_order'] ?>">
                <button type="submit" class="btn-move" title="Subir Posição" <?= ($index === 0) ? 'disabled style="opacity:0.4;"' : '' ?>>⬆️ Subir</button>
              </form>

              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="move_down">
                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                <input type="hidden" name="sort_order" value="<?= $cat['sort_order'] ?>">
                <button type="submit" class="btn-move" title="Descer Posição" <?= ($index === count($categories) - 1) ? 'disabled style="opacity:0.4;"' : '' ?>>⬇️ Descer</button>
              </form>

              <form method="POST" style="display:inline;" onsubmit="return confirm('Deseja excluir a categoria <?= htmlspecialchars(addslashes($cat['title'])) ?>?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                <button type="submit" class="btn-del" title="Excluir Categoria">🗑️</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</body>
</html>
