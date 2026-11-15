<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$db = getDBConnection();
ensureExtraTablesExist($db, 'mysql');
$id = $_GET['id'] ?? null;
$error = '';
$isEdit = !empty($id);

// Valores Padrão
$product = [
    'title' => '',
    'brand' => '',
    'sku' => '',
    'item_code' => '',
    'badge_1' => 'BRASIL',
    'badge_2' => 'LANÇAMENTO★',
    'discount_percentage' => 3,
    'regular_price' => '',
    'pix_price' => '',
    'pix_discount_percentage' => 7,
    'max_installments' => 10,
    'installment_value' => '',
    'carne_max_installments' => 24,
    'rating' => 4.9,
    'reviews_count' => 50,
    'questions_count' => 14,
    'review_gender' => 'ambos',
    'variation_name' => '',
    'variation_options' => '',
    'variation_default' => ''
];

$images = [];
$specs = [];
$warranties = [];

if ($isEdit) {
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $fetched = $stmt->fetch();
        if ($fetched) {
            $product = $fetched;
            
            // Carregar imagens
            $stmtImg = $db->prepare("SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC");
            $stmtImg->execute(['id' => $id]);
            $images = $stmtImg->fetchAll();
            $imagesText = !empty($images) ? implode("\n", array_column($images, 'image_url')) : '';

            // Carregar Ficha técnica
            $stmtSpec = $db->prepare("SELECT * FROM specifications WHERE product_id = :id");
            $stmtSpec->execute(['id' => $id]);
            $specs = $stmtSpec->fetchAll();

            // Carregar Garantias
            $stmtWar = $db->prepare("SELECT * FROM warranties WHERE product_id = :id");
            $stmtWar->execute(['id' => $id]);
            $warranties = $stmtWar->fetchAll();
        } else {
            $error = 'Produto não encontrado.';
            $isEdit = false;
        }
    } catch (Exception $e) {
        $error = 'Erro ao carregar produto: ' . $e->getMessage();
    }
}

// FUNÇÃO GERADORA DE AVALIAÇÕES REALISTAS POR GÊNERO
function generateFormReviews($count, $gender) {
    $maleNames = [
        'Francisco', 'Carlos Eduardo', 'Marcelo Santos', 'Rodrigo Lima', 'João Pedro', 
        'Fernando Alves', 'Bruno Henrique', 'Gustavo Ferreira', 'Lucas Oliveira', 'Matheus Costa', 
        'Paulo Roberto', 'Felipe Mendes', 'Thiago Rocha', 'Diego Martins', 'Rafael Barbosa',
        'Guilherme Silva', 'Eduardo Pereira', 'Renato Souza', 'Vinicius Castro', 'Gabriel Ribeiro',
        'Leandro Ramos', 'Marcos Vinicius', 'Alexandre Borges', 'Ricardo Santos', 'Mauricio Lima'
    ];
    
    $femaleNames = [
        'Juliana M.', 'Patricia Souza', 'Camila Silva', 'Amanda Oliveira', 'Ana Lucia', 
        'Mariana Costa', 'Beatriz Lima', 'Fernanda Rocha', 'Cristiana Ferreira', 'Vanessa Santos', 
        'Bruna Garcia', 'Leticia Almeida', 'Juliana Ribeiro', 'Gabriela Duarte', 'Larissa Ramos',
        'Carla Cristina', 'Renata Fonseca', 'Aline Cardoso', 'Daniela Martins', 'Jessica Nogueira',
        'Tatiane Lopes', 'Priscila Fernandes', 'Monique Reis', 'Thais Carvalho', 'Luciana M.'
    ];

    $comments = [
        "Excelente produto! Ótimo custo-benefício, entregou super rápido em 2 dias pelas Casas Bahia! Produto muito bem embalado e funcionando perfeitamente.",
        "Superou minhas expectativas! Desempenho e acabamento impecáveis, qualidade de alto nível. Recomendo demais!",
        "Produto excelente, fácil de instalar e usar no dia a dia. Chegou bem antes do prazo previsto.",
        "Muito satisfeito com a compra! Valeu muito a pena, entrega rápida e produto original.",
        "Comprei para a minha família e todos adoraram! Qualidade impressionante.",
        "Ótima compra, chegou em perfeitas condições. As Casas Bahia sempre entregando o melhor serviço.",
        "Produto muito bom, atendeu 100% das minhas necessidades. Recomendo a todos!",
        "Muito prático e bonito! A qualidade do material é muito superior aos concorrentes da mesma faixa de preço.",
        "Chegou certinho, nota 10 para o vendedor e para as Casas Bahia. Voltarei a comprar com certeza!",
        "Excelente custo-benefício. O manuseio é simples e intuitivo."
    ];

    $months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto'];
    $reviews = [];
    $targetCount = max(1, $count);

    for ($i = 0; $i < $targetCount; $i++) {
        if ($gender === 'homem') {
            $name = $maleNames[array_rand($maleNames)];
        } else if ($gender === 'mulher') {
            $name = $femaleNames[array_rand($femaleNames)];
        } else {
            $pool = ($i % 2 === 0) ? $maleNames : $femaleNames;
            $name = $pool[array_rand($pool)];
        }

        $rating = (rand(1, 10) > 1) ? 5 : 4;
        $day = rand(1, 28);
        $month = $months[array_rand($months)];
        $dateText = "Avaliado em $day de $month de 2026";
        $comment = $comments[array_rand($comments)];

        $reviews[] = [
            'author' => $name,
            'rating' => $rating,
            'review_date' => $dateText,
            'content' => $comment
        ];
    }

    return $reviews;
}

// Processar Formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $item_code = trim($_POST['item_code'] ?? '');
    $badge_1 = trim($_POST['badge_1'] ?? 'BRASIL');
    $badge_2 = trim($_POST['badge_2'] ?? 'LANÇAMENTO★');
    $discount_percentage = intval($_POST['discount_percentage'] ?? 0);
    $regular_price = floatval($_POST['regular_price'] ?? 0);
    $pix_price = floatval($_POST['pix_price'] ?? 0);
    if ($regular_price > 0 && $pix_price > 0 && $regular_price > $pix_price) {
        $pix_discount_percentage = round((($regular_price - $pix_price) / $regular_price) * 100);
    } else {
        $pix_discount_percentage = intval($_POST['pix_discount_percentage'] ?? 7);
    }
    $discount_percentage = $pix_discount_percentage;
    $max_installments = intval($_POST['max_installments'] ?? 10);
    $installment_value = $max_installments > 0 ? ($regular_price / $max_installments) : $regular_price;
    $carne_max_installments = intval($_POST['carne_max_installments'] ?? 24);
    $rating = floatval($_POST['rating'] ?? 4.9);
    $reviews_count = intval($_POST['reviews_count'] ?? 50);
    $questions_count = intval($_POST['questions_count'] ?? 14);
    $review_gender = trim($_POST['review_gender'] ?? 'ambos');
    $variation_name = trim($_POST['variation_name'] ?? '');
    $variation_options = trim($_POST['variation_options'] ?? '');
    $variation_default = trim($_POST['variation_default'] ?? '');

    // Imagens postadas
    $postImages = array_filter(array_map('trim', explode("\n", $_POST['images_urls'] ?? '')));

    if (empty($title) || $regular_price <= 0 || $pix_price <= 0) {
        $error = 'Por favor, preencha o título do produto e valores válidos.';
    } else {
        try {
            $db->beginTransaction();

            if ($isEdit) {
                // Atualizar Produto Existente
                $stmt = $db->prepare("
                    UPDATE products SET 
                        title = :title, brand = :brand, sku = :sku, item_code = :item_code,
                        badge_1 = :badge_1, badge_2 = :badge_2, discount_percentage = :discount_percentage,
                        regular_price = :regular_price, pix_price = :pix_price, pix_discount_percentage = :pix_discount_percentage,
                        max_installments = :max_installments, installment_value = :installment_value,
                        carne_max_installments = :carne_max_installments, rating = :rating,
                        reviews_count = :reviews_count, questions_count = :questions_count,
                        variation_name = :vname, variation_options = :vopts, variation_default = :vdef
                    WHERE id = :id
                ");
                $stmt->execute([
                    'title' => $title, 'brand' => $brand, 'sku' => $sku, 'item_code' => $item_code,
                    'badge_1' => $badge_1, 'badge_2' => $badge_2, 'discount_percentage' => $discount_percentage,
                    'regular_price' => $regular_price, 'pix_price' => $pix_price, 'pix_discount_percentage' => $pix_discount_percentage,
                    'max_installments' => $max_installments, 'installment_value' => $installment_value,
                    'carne_max_installments' => $carne_max_installments, 'rating' => $rating,
                    'reviews_count' => $reviews_count, 'questions_count' => $questions_count,
                    'vname' => $variation_name, 'vopts' => $variation_options, 'vdef' => $variation_default,
                    'id' => $id
                ]);

                $productId = $id;
            } else {
                // Inserir Novo Produto
                $stmt = $db->prepare("
                    INSERT INTO products (
                        title, brand, sku, item_code, badge_1, badge_2, discount_percentage,
                        regular_price, pix_price, pix_discount_percentage, max_installments,
                        installment_value, carne_max_installments, rating, reviews_count, questions_count,
                        variation_name, variation_options, variation_default
                    ) VALUES (
                        :title, :brand, :sku, :item_code, :badge_1, :badge_2, :discount_percentage,
                        :regular_price, :pix_price, :pix_discount_percentage, :max_installments,
                        :installment_value, :carne_max_installments, :rating, :reviews_count, :questions_count,
                        :vname, :vopts, :vdef
                    )
                ");
                $stmt->execute([
                    'title' => $title, 'brand' => $brand, 'sku' => $sku ?: 'SKU-' . time(), 'item_code' => $item_code ?: rand(100000, 999999),
                    'badge_1' => $badge_1, 'badge_2' => $badge_2, 'discount_percentage' => $discount_percentage,
                    'regular_price' => $regular_price, 'pix_price' => $pix_price, 'pix_discount_percentage' => $pix_discount_percentage,
                    'max_installments' => $max_installments, 'installment_value' => $installment_value,
                    'carne_max_installments' => $carne_max_installments, 'rating' => $rating,
                    'reviews_count' => $reviews_count, 'questions_count' => $questions_count,
                    'vname' => $variation_name, 'vopts' => $variation_options, 'vdef' => $variation_default
                ]);
                $driver = strtolower($db->getAttribute(PDO::ATTR_DRIVER_NAME) ?? 'mysql');
                $productId = null;
                if ($driver === 'pgsql') {
                    try { $productId = $db->lastInsertId('products_id_seq'); } catch (Exception $eSeq) {}
                    if (!$productId) {
                        try { $productId = $db->query("SELECT currval(pg_get_serial_sequence('products', 'id'))")->fetchColumn(); } catch (Exception $eSeq2) {}
                    }
                } else {
                    $productId = $db->lastInsertId();
                }
                if (!$productId) {
                    $productId = $db->query("SELECT MAX(id) FROM products")->fetchColumn();
                }
            }

            // 1. Processar upload de fotos enviadas do computador
            $uploadedUrls = [];
            $uploadDir = __DIR__ . '/../assets/images/uploads/';
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            if (!empty($_FILES['product_files']['name'][0])) {
                foreach ($_FILES['product_files']['name'] as $key => $filename) {
                    if ($_FILES['product_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['product_files']['tmp_name'][$key];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'])) {
                            $newFilename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                            $targetPath = $uploadDir . $newFilename;
                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $uploadedUrls[] = 'assets/images/uploads/' . $newFilename;
                            }
                        }
                    }
                }
            }

            // Combine URLs digitadas manualmente no textarea + fotos novas enviadas do computador
            $allFinalImages = array_merge($postImages, $uploadedUrls);

            // Atualizar Imagens da Galeria no Banco de Dados
            if (!empty($allFinalImages)) {
                $db->prepare("DELETE FROM product_images WHERE product_id = :pid")->execute(['pid' => $productId]);
                $stmtImg = $db->prepare("INSERT INTO product_images (product_id, image_url, is_main, sort_order) VALUES (:pid, :url, :is_main, :sort)");
                $sort = 1;
                foreach ($allFinalImages as $imgUrl) {
                    $stmtImg->execute([
                        'pid' => $productId,
                        'url' => $imgUrl,
                        'is_main' => ($sort === 1) ? 1 : 0,
                        'sort' => $sort
                    ]);
                    $sort++;
                }
            }

            // MANTER AVALIAÇÕES EXISTENTES (ESPECIALMENTE AS COM FOTOS)
            // SÓ GERA AVALIAÇÕES PADRÃO SE O PRODUTO NÃO TIVER NENHUMA AVALIAÇÃO CADASTRADA
            $stmtChkRev = $db->prepare("SELECT COUNT(*) FROM product_reviews WHERE product_id = :pid");
            $stmtChkRev->execute(['pid' => $productId]);
            $existingRevCount = (int)$stmtChkRev->fetchColumn();

            if ($existingRevCount === 0) {
                $generated = generateFormReviews($reviews_count, $review_gender);
                $stmtRev = $db->prepare("INSERT INTO product_reviews (product_id, author, reviewer_name, rating, rating_stars, review_date, content, review_text) VALUES (:pid, :author, :rev_name, :rating, :rating_stars, :rdate, :content, :rev_text)");

                foreach ($generated as $rItem) {
                    $stmtRev->execute([
                        'pid' => $productId,
                        'author' => $rItem['author'],
                        'rev_name' => $rItem['author'],
                        'rating' => $rItem['rating'],
                        'rating_stars' => $rItem['rating'],
                        'rdate' => $rItem['review_date'],
                        'content' => $rItem['content'],
                        'rev_text' => $rItem['content']
                    ]);
                }
            }

            $db->commit();
            header('Location: index.php?msg=' . urlencode('Produto e avaliações salvas com sucesso!'));
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Erro ao salvar produto: ' . $e->getMessage();
        }
    }
}

// Preparar URLs de imagem para a textarea
$imagesText = implode("\n", array_column($images, 'image_url'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Editar Produto' : 'Novo Produto' ?> | Painel Admin</title>
  <style>
    :root { --primary: #003399; --success: #168019; --danger: #e30613; --gray: #f5f6f8; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--gray); margin: 0; color: #333; }
    header { background: #ffffff; border-bottom: 1px solid #e0e0e0; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10; }
    .brand { font-size: 20px; font-weight: 900; color: var(--primary); }
    .brand span { color: var(--danger); }
    .user-nav { display: flex; align-items: center; gap: 16px; font-size: 14px; }
    .btn-back { background: #eaeaea; color: #333; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; }
    .container { max-width: 900px; margin: 24px auto; padding: 0 16px; }
    .card { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .form-title { font-size: 20px; font-weight: 700; color: #222; margin-top: 0; margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    label { display: block; font-size: 13px; font-weight: 700; color: #555; margin-bottom: 6px; }
    input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; box-sizing: border-box; outline: none; background: white; }
    textarea { height: 100px; font-family: inherit; }
    input:focus, select:focus, textarea:focus { border-color: var(--primary); }
    small { font-size: 11px; color: #777; display: block; margin-top: 4px; }
    .btn-save { background: var(--success); color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; }
    .btn-cancel { background: #888; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-size: 15px; font-weight: bold; margin-left: 10px; }
    .alert-danger { background: #ffebee; color: #c62828; padding: 12px; border-radius: 6px; margin-bottom: 16px; border: 1px solid #ffcdd2; }
  </style>
</head>
<body>

  <header>
    <div class="brand">CASAS<span>BAHIA</span> Admin</div>
    <div class="user-nav">
      <a href="index.php" class="btn-back">← Voltar ao Painel</a>
    </div>
  </header>

  <div class="container">
    <div class="card">
      <h1 class="form-title"><?= $isEdit ? 'Editar Produto: ' . htmlspecialchars($product['title']) : 'Adicionar Novo Produto' ?></h1>

      <?php if ($error): ?>
        <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="title">Título Completo do Produto *</label>
          <input type="text" id="title" name="title" value="<?= htmlspecialchars($product['title']) ?>" required placeholder="Ex: Smart TV 43 AOC Full HD Wi-Fi Roku TV">
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label for="brand">Marca / Fabricante *</label>
            <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($product['brand']) ?>" required placeholder="Ex: AOC, Positivo, Samsung, LG">
          </div>
          <div class="form-group">
            <label for="item_code">Código do Item / Referência</label>
            <input type="text" id="item_code" name="item_code" value="<?= htmlspecialchars($product['item_code']) ?>" placeholder="Ex: 55071718">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label for="sku">SKU do Produto</label>
            <input type="text" id="sku" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" placeholder="Ex: AOC-43S5155">
          </div>
          <div class="form-group">
            <label for="discount_percentage">Selo de Desconto Geral (% OFF)</label>
            <input type="number" id="discount_percentage" name="discount_percentage" value="<?= htmlspecialchars($product['discount_percentage']) ?>">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label for="badge_1">Selo 1 (Amarelo)</label>
            <input type="text" id="badge_1" name="badge_1" value="<?= htmlspecialchars($product['badge_1']) ?>" placeholder="Ex: BRASIL">
          </div>
          <div class="form-group">
            <label for="badge_2">Selo 2 (Vermelho)</label>
            <input type="text" id="badge_2" name="badge_2" value="<?= htmlspecialchars($product['badge_2']) ?>" placeholder="Ex: LANÇAMENTO★">
          </div>
        </div>

        <h3 style="margin-top: 24px; color: #003399; font-size: 16px;">💰 Preços & Pagamento</h3>
        
        <div class="grid-2">
          <div class="form-group">
            <label for="regular_price">Preço Original / Cartão (R$) *</label>
            <input type="number" step="0.01" id="regular_price" name="regular_price" value="<?= htmlspecialchars($product['regular_price']) ?>" required placeholder="1699.00">
          </div>
          <div class="form-group">
            <label for="pix_price">Preço no Pix (R$) *</label>
            <input type="number" step="0.01" id="pix_price" name="pix_price" value="<?= htmlspecialchars($product['pix_price']) ?>" required placeholder="1580.07">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label for="pix_discount_percentage">% Desconto no Pix</label>
            <input type="number" id="pix_discount_percentage" name="pix_discount_percentage" value="<?= htmlspecialchars($product['pix_discount_percentage']) ?>" placeholder="7">
          </div>
          <div class="form-group">
            <label for="max_installments">Máximo de Parcelas sem Juros</label>
            <input type="number" id="max_installments" name="max_installments" value="<?= htmlspecialchars($product['max_installments']) ?>" placeholder="10">
          </div>
        </div>

        <h3 style="margin-top: 24px; color: #003399; font-size: 16px;">⚙️ Opções de Variação (Voltagem / Tamanho / Cor)</h3>
        <div style="background: #f8fafc; border: 1.5px solid #d0d7de; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: 700; color: #333; user-select: none;">
            <input type="checkbox" id="enableVariation" name="enable_variation" value="1"
              onchange="toggleVariationFields()"
              <?= !empty($product['variation_name']) ? 'checked' : '' ?>
              style="width: 18px; height: 18px; accent-color: #003399; cursor: pointer; flex-shrink: 0;">
            Este produto possui variações (Voltagem, Tamanho, Cor, etc.)
          </label>

          <div id="variationFields" style="<?= !empty($product['variation_name']) ? '' : 'display: none;' ?> margin-top: 16px;">
            <div class="grid-3" style="grid-template-columns: 1fr 1fr 1fr;">
              <div class="form-group">
                <label for="variation_name">Nome da Variação</label>
                <input type="text" id="variation_name" name="variation_name" value="<?= htmlspecialchars($product['variation_name'] ?? '') ?>" placeholder="Ex: Voltagem ou Tamanho">
              </div>
              <div class="form-group">
                <label for="variation_options">Opções (separadas por vírgula)</label>
                <input type="text" id="variation_options" name="variation_options" value="<?= htmlspecialchars($product['variation_options'] ?? '') ?>" placeholder='Ex: 110V, 220V ou 43", 55"'>
              </div>
              <div class="form-group">
                <label for="variation_default">Opção Padrão Selecionada</label>
                <input type="text" id="variation_default" name="variation_default" value="<?= htmlspecialchars($product['variation_default'] ?? '') ?>" placeholder='Ex: 220V ou 55"'>
              </div>
            </div>
          </div>
        </div>

        <script>
        function toggleVariationFields() {
          var chk = document.getElementById('enableVariation');
          var fields = document.getElementById('variationFields');
          if (chk.checked) {
            fields.style.display = 'block';
          } else {
            fields.style.display = 'none';
            document.getElementById('variation_name').value = '';
            document.getElementById('variation_options').value = '';
            document.getElementById('variation_default').value = '';
          }
        }
        </script>

        <h3 style="margin-top: 24px; color: #003399; font-size: 16px;">📸 Galeria de Imagens do Produto</h3>
        
        <div class="form-group" style="background: #f8fafc; border: 2px dashed #003399; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
          <label for="product_files" style="font-size: 15px; font-weight: 800; color: #003399; display: block; margin-bottom: 8px; cursor: pointer;">
            📁 Enviar Fotos do seu Computador / Celular
          </label>
          <input type="file" id="product_files" name="product_files[]" multiple accept="image/*" style="font-size: 14px; padding: 8px; cursor: pointer;">
          <small style="display: block; margin-top: 8px; color: #64748b;">Você pode selecionar uma ou várias imagens do seu computador (.jpg, .png, .webp, .gif).</small>
        </div>

        <?php if (!empty($images)): ?>
          <label style="font-size: 13px; font-weight: 700; color: #444; margin-top: 10px; display: block;">Fotos Cadastradas do Produto (<?= count($images) ?> fotos):</label>
          <div style="display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; margin-bottom: 12px;">
            <?php foreach ($images as $img): 
              $displayImgUrl = (strpos($img['image_url'], 'http') === 0 || strpos($img['image_url'], 'assets/') === 0) ? $img['image_url'] : '../' . $img['image_url'];
            ?>
              <div style="width: 84px; height: 84px; border: 1.5px solid #003399; border-radius: 8px; overflow: hidden; flex-shrink: 0; background: #ffffff; padding: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                <img src="<?= htmlspecialchars($displayImgUrl) ?>" style="width: 100%; height: 100%; object-fit: contain;" alt="Foto produto">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label for="images_urls">Links das Imagens (Uma URL por linha)</label>
          <textarea id="images_urls" name="images_urls" placeholder="https://exemplo.com/imagem1.jpg&#10;https://exemplo.com/imagem2.jpg"><?= htmlspecialchars($imagesText) ?></textarea>
          <small>URLs de imagens web ou caminhos salvos. Novas fotos enviadas do computador serão adicionadas automaticamente.</small>
        </div>

        <h3 style="margin-top: 24px; color: #003399; font-size: 16px;">⭐ Configuração de Avaliações</h3>
        <div style="background: #ffffff; border: 1.5px solid #d0d7de; padding: 18px; border-radius: 8px; margin-bottom: 20px;">
          <div class="grid-3" style="margin-bottom: 14px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label for="rating">Nota Média (0 a 5)</label>
              <input type="number" step="0.1" id="rating" name="rating" value="<?= htmlspecialchars($product['rating']) ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label for="reviews_count">Qtd. de Avaliações</label>
              <input type="number" id="reviews_count" name="reviews_count" value="<?= htmlspecialchars($product['reviews_count']) ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label for="review_gender">Gênero das Avaliações</label>
              <select name="review_gender" id="review_gender">
                <option value="ambos" <?= ($product['review_gender'] ?? '') === 'ambos' ? 'selected' : '' ?>>Ambos (Homem e Mulher)</option>
                <option value="homem" <?= ($product['review_gender'] ?? '') === 'homem' ? 'selected' : '' ?>>Apenas Homens</option>
                <option value="mulher" <?= ($product['review_gender'] ?? '') === 'mulher' ? 'selected' : '' ?>>Apenas Mulheres</option>
              </select>
            </div>
          </div>
          <div style="background: #f0f5ff; border: 1px solid #cce0ff; padding: 10px 14px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <span style="font-size: 13px; color: #003399; font-weight: 600;">📸 Deseja cadastrar fotos reais para as avaliações deste produto?</span>
            <a href="reviews.php<?= $isEdit && !empty($id) ? '?product_id='.$id : '' ?>" target="_blank" style="background: #003399; color: #fff; padding: 6px 12px; border-radius: 5px; text-decoration: none; font-weight: 700; font-size: 12px; white-space: nowrap;">Gerenciar Fotos das Avaliações →</a>
          </div>
        </div>

        <div style="margin-top: 24px; border-top: 1px solid #eee; padding-top: 20px;">
          <button type="submit" class="btn-save">💾 Salvar Produto</button>
          <a href="index.php" class="btn-cancel">Cancelar</a>
        </div>

      </form>
    </div>
  </div>

</body>
</html>
