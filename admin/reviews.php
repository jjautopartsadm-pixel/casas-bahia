<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
$msgSuccess = '';
$msgError = '';

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

// Excluir Avaliação
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $delId = intval($_GET['id']);
        $stmtDelImg = $db->prepare("DELETE FROM review_images WHERE review_id = :rid");
        $stmtDelImg->execute(['rid' => $delId]);

        $stmtDel = $db->prepare("DELETE FROM product_reviews WHERE id = :id");
        $stmtDel->execute(['id' => $delId]);
        $msgSuccess = "Avaliação removida com sucesso!";
    } catch (Exception $e) {
        $msgError = "Erro ao excluir avaliação: " . $e->getMessage();
    }
}

// Cadastrar Nova Avaliação com Fotos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    try {
        $prodId = intval($_POST['product_id'] ?? 0);
        $author = trim($_POST['author'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $reviewDate = trim($_POST['review_date'] ?? ('Avaliado em ' . date('d') . ' de ' . date('m') . ' de ' . date('Y')));
        $content = trim($_POST['content'] ?? '');
        $photoUrlsRaw = trim($_POST['photo_urls'] ?? '');

        if ($prodId <= 0 || empty($author) || empty($content)) {
            $msgError = "Por favor, selecione um produto, informe o nome do comprador e o texto da avaliação.";
        } else {
            $db->beginTransaction();

            $stmtIns = $db->prepare("INSERT INTO product_reviews (product_id, author, reviewer_name, rating, rating_stars, review_date, content, review_text) VALUES (:pid, :author, :rev_name, :rating, :rating_stars, :rdate, :content, :rev_text)");
            $stmtIns->execute([
                'pid' => $prodId,
                'author' => $author,
                'rev_name' => $author,
                'rating' => $rating,
                'rating_stars' => $rating,
                'rdate' => $reviewDate,
                'content' => $content,
                'rev_text' => $content
            ]);

            $newReviewId = $db->lastInsertId();

            // Processar URLs de fotos
            $urls = array_filter(array_map('trim', explode("\n", $photoUrlsRaw)));
            if (!empty($urls)) {
                $stmtImg = $db->prepare("INSERT INTO review_images (review_id, image_url) VALUES (:rid, :url)");
                foreach ($urls as $u) {
                    if (!empty($u)) {
                        $stmtImg->execute(['rid' => $newReviewId, 'url' => $u]);
                    }
                }
            }

            $db->commit();
            $msgSuccess = "🎉 Avaliação cadastrada com sucesso com " . count($urls) . " foto(s) anexada(s)!";
            $productId = $prodId;
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $msgError = "Erro ao cadastrar avaliação: " . $e->getMessage();
    }
}

// Carregar lista de produtos
$products = $db->query("SELECT id, title, brand FROM products ORDER BY id DESC")->fetchAll();
if (!$productId && !empty($products)) {
    $productId = $products[0]['id'];
}

// Carregar avaliações do produto selecionado
$reviews = [];
if ($productId) {
    $stmtR = $db->prepare("SELECT * FROM product_reviews WHERE product_id = :pid ORDER BY id DESC");
    $stmtR->execute(['pid' => $productId]);
    $reviews = $stmtR->fetchAll();

    foreach ($reviews as &$r) {
        $stmtI = $db->prepare("SELECT * FROM review_images WHERE review_id = :rid ORDER BY id ASC");
        $stmtI->execute(['rid' => $r['id']]);
        $r['images'] = $stmtI->fetchAll();
    }
    unset($r);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Avaliações c/ Fotos - Casas Bahia Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --primary: #003399; --danger: #e30613; --bg: #f4f6f9; }
    body { font-family: 'Open Sans', sans-serif; background: var(--bg); margin: 0; padding: 0; color: #333; }
    header { background: var(--primary); color: white; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
    .brand { font-size: 20px; font-weight: 800; }
    .btn-back { color: white; text-decoration: none; font-weight: 700; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; }
    .container { max-width: 1000px; margin: 24px auto; padding: 0 16px; }
    .card { background: white; border-radius: 10px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px; }
    h2 { color: var(--primary); margin-top: 0; font-size: 18px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
    .form-group { margin-bottom: 16px; }
    label { display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #444; }
    input[type="text"], select, textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
    textarea { height: 90px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .btn-submit { background: #168019; color: white; border: none; padding: 14px 24px; border-radius: 6px; font-weight: 800; cursor: pointer; width: 100%; font-size: 15px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; padding: 14px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
    .alert-danger { background: #ffebee; color: #c62828; padding: 14px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; }
    
    .review-list-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 14px; background: #fafafa; }
    .review-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .author-name { font-weight: 800; font-size: 15px; color: #1e293b; }
    .stars { color: #e67e22; font-size: 16px; }
    .date-text { font-size: 12px; color: #64748b; }
    .photos-preview-row { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
    .photo-thumb { width: 70px; height: 70px; border-radius: 6px; object-fit: cover; border: 1px solid #cbd5e1; }
    .btn-delete { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 4px; font-weight: 700; text-decoration: none; font-size: 12px; }
  </style>
</head>
<body>

  <header>
    <div class="brand">Gerenciador de Avaliações c/ Fotos</div>
    <a href="index.php" class="btn-back">← Voltar ao Painel Admin</a>
  </header>

  <div class="container">

    <?php if ($msgSuccess): ?>
      <div class="alert-success"><?= $msgSuccess ?></div>
    <?php endif; ?>
    <?php if ($msgError): ?>
      <div class="alert-danger"><?= $msgError ?></div>
    <?php endif; ?>

    <!-- SELETOR DE PRODUTO -->
    <div class="card">
      <h2>1. Selecionar Produto para Gerenciar Avaliações</h2>
      <form method="GET" action="reviews.php">
        <label for="product_id">Escolha o Produto:</label>
        <select name="product_id" id="product_id" onchange="this.form.submit()">
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($p['id'] == $productId) ? 'selected' : '' ?>>
              #<?= $p['id'] ?> - <?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['brand']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <!-- FORMULÁRIO DE NOVA AVALIAÇÃO COM FOTOS -->
    <div class="card">
      <h2>2. Cadastrar Nova Avaliação (Com ou Sem Fotos)</h2>
      <form method="POST" action="reviews.php">
        <input type="hidden" name="action" value="add_review">
        <input type="hidden" name="product_id" value="<?= $productId ?>">

        <div class="grid-2">
          <div class="form-group">
            <label for="author">Nome do Comprador / Autor *</label>
            <input type="text" name="author" id="author" placeholder="Ex: Camila Silva ou Carlos Eduardo" required>
          </div>
          <div class="form-group">
            <label for="rating">Nota em Estrelas *</label>
            <select name="rating" id="rating">
              <option value="5" selected>5 Estrelas (★★★★★)</option>
              <option value="4">4 Estrelas (★★★★☆)</option>
              <option value="3">3 Estrelas (★★★☆☆)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="review_date">Texto de Data da Avaliação</label>
          <input type="text" name="review_date" id="review_date" value="Avaliado em <?= date('d') ?> de agosto de <?= date('Y') ?>">
        </div>

        <div class="form-group">
          <label for="content">Texto do Depoimento / Avaliação *</label>
          <textarea name="content" id="content" placeholder="Ex: Chegou extremamente rápido! Produto de excelente qualidade, veio bem embalado e superou minhas expectativas." required></textarea>
        </div>

        <div class="form-group" style="background: #f0f5ff; padding: 14px; border-radius: 8px; border: 1px solid #cce0ff;">
          <label for="photo_urls" style="color: #003399; display: flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            <span>Fotos do Comprador (URLs de imagem, uma por linha):</span>
          </label>
          <textarea name="photo_urls" id="photo_urls" placeholder="https://exemplo.com/foto1.jpg&#10;https://exemplo.com/foto2.jpg" style="height: 80px;"></textarea>
          <span style="font-size: 12px; color: #555;">💡 As avaliações com fotos <strong>aparecerão automaticamente primeiro</strong> no topo da página do produto.</span>
        </div>

        <button type="submit" class="btn-submit">➕ Salvar Nova Avaliação</button>
      </form>
    </div>

    <!-- LISTA DE AVALIAÇÕES CADASTRADAS -->
    <div class="card">
      <h2>3. Avaliações Cadastradas para este Produto (<?= count($reviews) ?>)</h2>
      
      <?php if (empty($reviews)): ?>
        <p style="color: #666;">Nenhuma avaliação cadastrada para este produto.</p>
      <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
          <div class="review-list-item">
            <div class="review-header-row">
              <div>
                <span class="author-name"><?= htmlspecialchars($rev['author']) ?></span>
                <span class="stars" style="margin-left: 10px;"><?= str_repeat('★', intval($rev['rating'])) ?></span>
                <?php if (!empty($rev['images'])): ?>
                  <span style="background: #e8f5e9; color: #168019; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 4px; margin-left: 8px; display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#168019" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>POSSUI <?= count($rev['images']) ?> FOTO(S)</span>
                  </span>
                <?php endif; ?>
              </div>
              <div>
                <span class="date-text"><?= htmlspecialchars($rev['review_date']) ?></span>
                <a href="reviews.php?product_id=<?= $productId ?>&action=delete&id=<?= $rev['id'] ?>" class="btn-delete" style="margin-left: 10px;" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">Excluir</a>
              </div>
            </div>

            <p style="font-size: 14px; margin: 8px 0; color: #333;"><?= nl2br(htmlspecialchars($rev['content'])) ?></p>

            <?php if (!empty($rev['images'])): ?>
              <div class="photos-preview-row">
                <?php foreach ($rev['images'] as $img): ?>
                  <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Foto" class="photo-thumb">
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>

  </div>

</body>
</html>
