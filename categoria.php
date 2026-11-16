<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/domain_guard.php';
checkDomainAccess();
require_once __DIR__ . '/includes/pixel_tracker.php';

$db = getDBConnection();

$slug = strtolower(trim($_GET['cat'] ?? $_GET['slug'] ?? 'tvs'));
if (empty($slug)) $slug = 'tvs';

// Mapeamento Oficial de Categorias
$categoryMap = [
    'tvs' => [
        'slug' => 'tvs',
        'title' => "TV's e Vídeo",
        'subtitle' => 'Smart TVs 4K, QLED, OLED e Full HD com o menor frete e desconto no Pix',
        'keywords' => ['tv', 'smart tv', 'televisor', 'led', 'qled', 'oled', 'roku', '4k', 'monitor']
    ],
    'iphone' => [
        'slug' => 'iphone',
        'title' => 'iPhones & Apple',
        'subtitle' => 'Toda a linha iPhone com tecnologia de ponta, entrega garantida e Pix com OFF',
        'keywords' => ['iphone', 'apple']
    ],
    'smartphones' => [
        'slug' => 'smartphones',
        'title' => 'Celulares e Smartphones',
        'subtitle' => 'Samsung Galaxy, Motorola, Xiaomi e as melhores marcas de smartphones',
        'keywords' => ['smartphone', 'celular', 'galaxy', 'moto', 'xiaomi', 'redmi', 'motorola', 'poco', 'realme']
    ],
    'eletros' => [
        'slug' => 'eletros',
        'title' => 'Eletrodomésticos e Eletroportáteis',
        'subtitle' => 'Air Fryers, Geladeiras, Máquinas de Lavar, Fogões e portáteis para sua casa',
        'keywords' => ['fritadeira', 'air fryer', 'airfryer', 'geladeira', 'fogão', 'fogao', 'micro-ondas', 'microondas', 'lavar', 'máquina', 'maquina', 'ventila', 'liquidi', 'batedeira', 'cafeteira', 'forno', 'cooktop', 'ar condicionado', 'mondial', 'britânia', 'philco', 'electrolux', 'brastemp', 'consul']
    ],
    'invasao' => [
        'slug' => 'invasao',
        'title' => 'Invasão de Ofertas (USE: DESCONTO)',
        'subtitle' => 'Produtos com descontos exclusivos de até 50% OFF e Cupom Especial',
        'keywords' => []
    ]
];

$currentCategory = $categoryMap[$slug] ?? [
    'slug' => $slug,
    'title' => ucfirst($slug),
    'subtitle' => 'Ofertas exclusivas com o menor preço e desconto no Pix',
    'keywords' => []
];

// Buscar Categorias Cadastradas para a Barra de Ícones
$categories = [];
try {
    $stmtC = $db->query("SELECT * FROM home_categories ORDER BY sort_order ASC, id ASC");
    $categories = $stmtC->fetchAll();
} catch (Exception $e) {}

if (empty($categories)) {
    $categories = [
        ['slug' => 'invasao', 'title' => 'USE: DESCONTO', 'image_url' => ''],
        ['slug' => 'tvs', 'title' => 'TV\'s', 'image_url' => 'assets/images/cat_tvs.jpg'],
        ['slug' => 'iphone', 'title' => 'iPhone', 'image_url' => 'assets/images/cat_iphone.jpg'],
        ['slug' => 'smartphones', 'title' => 'Smartphones', 'image_url' => 'assets/images/cat_smartphones.jpg'],
        ['slug' => 'eletros', 'title' => 'Eletros', 'image_url' => 'assets/images/cat_eletros.jpg']
    ];
}

// 1. Buscar todos os produtos do MySQL
$allProducts = [];
try {
    $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
    $allProducts = $stmt->fetchAll();
} catch (Exception $e) {}

// Fallback de segurança se o banco estiver vazio
if (empty($allProducts)) {
    $allProducts = [
        [
            'id' => 1,
            'title' => 'Smart TV 43 AOC Full HD Wi-Fi Roku TV',
            'brand' => 'AOC',
            'regular_price' => 1699.00,
            'pix_price' => 1580.07,
            'discount_percentage' => 13,
            'rating' => 4.9,
            'reviews_count' => 151,
            'max_installments' => 10,
            'installment_value' => 49.90,
            'category' => 'tvs'
        ]
    ];
}

// 2. Mapear imagens principais dos produtos
$productImages = [];
try {
    $stmtImgs = $db->query("SELECT product_id, image_url FROM product_images ORDER BY is_main DESC, id ASC");
    $allImgs = $stmtImgs->fetchAll();
    foreach ($allImgs as $imgRow) {
        if (!isset($productImages[$imgRow['product_id']])) {
            $productImages[$imgRow['product_id']] = $imgRow['image_url'];
        }
    }
} catch (Exception $e) {}

// 3. Filtrar produtos correspondentes à categoria selecionada
$filteredProducts = [];
$keywords = $currentCategory['keywords'] ?? [];

foreach ($allProducts as $p) {
    // A) Checagem direta pela coluna category se definida
    if (!empty($p['category']) && strcasecmp($p['category'], $slug) === 0) {
        $filteredProducts[] = $p;
        continue;
    }

    // B) Checagem por palavras-chave no título e marca
    if (!empty($keywords)) {
        $pTitle = mb_strtolower($p['title'] ?? '') . ' ' . mb_strtolower($p['brand'] ?? '');
        foreach ($keywords as $kw) {
            if (mb_strpos($pTitle, mb_strtolower($kw)) !== false) {
                $filteredProducts[] = $p;
                break;
            }
        }
    } else {
        // Se for categoria invasao ou sem palavras-chave especificas, inclui todos
        $filteredProducts[] = $p;
    }
}

// Se nenhum produto for encontrado na categoria específica, mostra todos para a página não ficar vazia
if (empty($filteredProducts)) {
    $filteredProducts = $allProducts;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= htmlspecialchars($currentCategory['title']) ?> - Ofertas Casas Bahia</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <!-- OPEN GRAPH / SOCIAL SHARE (WHATSAPP, FACEBOOK, TELEGRAM, TWITTER) -->
  <?php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $siteHost = $_SERVER['HTTP_HOST'] ?? 'meucarrinho.online';
    $baseUrl = $protocol . $siteHost;
    $catOgImage = $baseUrl . '/assets/images/casasbahia_home_share.jpg';
    $catOgTitle = $currentCategory['title'] . " - Ofertas Casas Bahia";
    $catOgDesc = $currentCategory['subtitle'] ?? ("Aproveite as melhores ofertas em " . $currentCategory['title'] . " com frete grátis e desconto no Pix nas Casas Bahia.");
    $catOgUrl = $baseUrl . '/categoria.php?cat=' . $slug;
  ?>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Casas Bahia">
  <meta property="og:title" content="<?= htmlspecialchars($catOgTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($catOgDesc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($catOgImage) ?>">
  <meta property="og:image:secure_url" content="<?= htmlspecialchars($catOgImage) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($catOgUrl) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($catOgTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($catOgDesc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($catOgImage) ?>">
  <?php renderFacebookPixel('ViewCategory', ['category_name' => $currentCategory['title']]); ?>
  <style>
    :root {
      --cb-blue-primary: #003399;
      --cb-blue-dark: #001a4d;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-yellow: #ffb800;
      --cb-gray-border: #e2e8f0;
      --cb-text-dark: #1e293b;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family); background-color: #ffffff; color: var(--cb-text-dark); line-height: 1.4; -webkit-font-smoothing: antialiased; }
    a { color: inherit; text-decoration: none; }

    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    /* CABEÇALHO DA CASAS BAHIA */
    .main-header { background-color: #000000; border-bottom: 1px solid #111111; position: relative; z-index: 10; }
    .header-top-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; }
    .header-left-group { display: flex; align-items: center; gap: 8px; }
    .brand-logo { font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; line-height: 1; display: flex; align-items: center; text-decoration: none; }
    .brand-logo span { color: #ffffff; }
    .header-actions { display: flex; gap: 14px; align-items: center; }
    .header-icon-btn { background: none; border: none; color: #ffffff; cursor: pointer; display: flex; align-items: center; padding: 2px; }
    .header-icon-btn svg { stroke: #ffffff !important; }

    .search-box-wrapper { padding: 0 16px 10px 16px; }
    .search-input-container { display: flex; align-items: center; background-color: #ffffff; border: 1px solid #ffffff; border-radius: 8px; padding: 9px 14px; }
    .search-input-container input { border: none; outline: none; width: 100%; font-size: 14px; font-weight: 400; color: #1e293b; background: transparent; }
    .search-input-container input::placeholder { color: #64748b; }
    .search-icon { color: #64748b; display: flex; align-items: center; }
    .search-icon svg { stroke: #64748b !important; }

    /* NAVEGAÇÃO ENTRE CATEGORIAS (CARROSSEL COM ÍCONES) */
    .category-scroll-container { display: flex; gap: 16px; overflow-x: auto; padding: 18px 16px; background-color: #ffffff; border-bottom: 1px solid #eeeeee; scrollbar-width: none; }
    .category-scroll-container::-webkit-scrollbar { display: none; }
    .cat-item-card { display: flex; flex-direction: column; align-items: center; min-width: 86px; text-align: center; cursor: pointer; text-decoration: none; color: inherit; flex-shrink: 0; }
    .cat-icon-circle { width: 86px; height: 86px; border-radius: 26px; background: #0a0a0a; border: none; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.09); flex-shrink: 0; transition: transform 0.2s, box-shadow 0.2s; }
    .cat-item-card.active .cat-icon-circle { box-shadow: 0 4px 14px rgba(0, 51, 153, 0.4); transform: scale(1.05); }
    .cat-icon-circle img, .cat-icon-circle video { width: 100% !important; height: 100% !important; object-fit: cover !important; border-radius: 26px !important; border: none !important; outline: none !important; display: block !important; transform: scale(1.05); }
    .cat-label { font-size: 12.5px; font-weight: 500; color: #003399; line-height: 1.25; text-align: center; white-space: nowrap; letter-spacing: -0.2px; }

    /* BANNER HERO DA CATEGORIA */
    .category-hero-banner { background: linear-gradient(135deg, #003399 0%, #001a4d 100%); margin: 16px; padding: 22px 20px; border-radius: 14px; color: #ffffff; box-shadow: 0 4px 14px rgba(0,51,153,0.18); }
    .cat-badge-tag { font-size: 11px; font-weight: 900; background: var(--cb-red); display: inline-block; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-bottom: 8px; }
    .cat-hero-title { font-size: 24px; font-weight: 900; line-height: 1.15; letter-spacing: -0.5px; margin-bottom: 6px; }
    .cat-hero-sub { font-size: 13px; opacity: 0.9; line-height: 1.4; }

    /* BARRA DE CONTAGEM E FILTRO */
    .results-info-bar { display: flex; align-items: center; justify-content: space-between; padding: 0 16px 14px 16px; font-size: 13px; color: #64748b; font-weight: 600; }
    .results-count strong { color: var(--cb-blue-primary); }

    /* GRADE DE 2 PRODUTOS POR LINHA */
    .category-products-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 16px 32px 16px; }
    
    .product-card-cb { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.03); text-decoration: none; color: inherit; width: 100%; min-width: 0; }
    .card-badge-animated { background-color: #e30613; color: #ffffff; padding: 0 10px; border-radius: 4px; text-align: center; font-weight: 900; width: fit-content; max-width: 130px; margin-bottom: 8px; height: 22px; line-height: 22px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; }
    .card-img-wrapper { width: 100%; height: 140px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .card-img-wrapper img, .card-img-wrapper video { max-width: 100%; max-height: 100%; object-fit: contain; }

    .card-product-title { font-size: 12px; font-weight: 600; color: #334155; line-height: 1.35; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 32px; }
    .card-stars-row { display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 11px; color: #eab308; font-weight: 700; }
    .card-stars-count { color: #64748b; font-size: 10px; font-weight: 500; }

    .card-price-old-line { font-size: 11px; color: #94a3b8; text-decoration: line-through; display: flex; align-items: center; gap: 4px; margin-bottom: 2px; }
    .card-discount-tag { background-color: #15803d; color: #ffffff; font-size: 9px; font-weight: 800; padding: 1px 4px; border-radius: 3px; }

    .card-installment-txt { font-size: 11px; color: #475569; font-weight: 500; margin-bottom: 4px; line-height: 1.25; }
    .card-price-pix-bold { font-size: 18px; font-weight: 900; color: #003399; letter-spacing: -0.4px; margin-bottom: 6px; }
    .card-badge-frete { background-color: #ffb800; color: #000000; font-size: 9px; font-weight: 900; padding: 3px 6px; border-radius: 4px; text-align: center; text-transform: uppercase; margin-top: auto; display: inline-block; }

    /* RODAPÉ CASAS BAHIA */
    .cb-footer-container { background-color: #fafafa; border-top: 1px solid #e2e8f0; padding: 24px 16px; margin-top: 24px; text-align: center; font-size: 12px; color: #64748b; }
  </style>
</head>
<body>

  <div class="top-red-line"></div>

  <!-- STICKY HEADER -->
  <header class="main-header">
    <div class="header-top-row">
      <div class="header-left-group">
        <a href="home.php" class="header-icon-btn" aria-label="Voltar para a Home">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        
        <a href="home.php" class="brand-logo">
          CASAS<span>BAHIA</span>
        </a>
      </div>

      <div class="header-actions">
        <button class="header-icon-btn" onclick="window.location.href='carrinho.php?empty=1'" aria-label="Carrinho">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </button>
      </div>
    </div>

    <!-- CAMPO DE BUSCA -->
    <div class="search-box-wrapper">
      <div class="search-input-container">
        <input type="text" placeholder="Pesquisar em <?= htmlspecialchars($currentCategory['title']) ?>..." aria-label="Pesquisar">
        <span class="search-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </span>
      </div>
    </div>
  </header>

  <?php require_once __DIR__ . '/includes/search_modal.php'; ?>

  <!-- BARRA DE NAVEGAÇÃO DE CATEGORIAS -->
  <div class="category-scroll-container">
    <?php foreach ($categories as $cat): ?>
      <?php $isActive = ($cat['slug'] === $slug); ?>
      <a href="categoria.php?cat=<?= urlencode($cat['slug']) ?>" class="cat-item-card <?= $isActive ? 'active' : '' ?>">
        <div class="cat-icon-circle">
          <?php if (!empty($cat['image_url'])): ?>
            <?php 
              $cUrl = (strpos($cat['image_url'], 'http') === 0) ? $cat['image_url'] : $cat['image_url'];
              $cExt = strtolower(pathinfo(parse_url($cUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            ?>
            <?php if (in_array($cExt, ['mp4', 'webm', 'mov'])): ?>
              <video src="<?= htmlspecialchars($cUrl) ?>" autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px; pointer-events: none; display: block; border: none;"></video>
            <?php else: ?>
              <img src="<?= htmlspecialchars($cUrl) ?>" alt="<?= htmlspecialchars($cat['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px; display: block; border: none;">
            <?php endif; ?>
          <?php else: ?>
            <div style="background: linear-gradient(135deg, #e30613 0%, #003399 100%); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; padding: 4px; border-radius: 20px;">
              <span style="font-size: 10px; font-weight: 900; line-height: 1.1; text-transform: uppercase;">INVASÃO</span>
              <span style="font-size: 9px; font-weight: 800; color: #ffb800; margin-top: 2px;">OFERTAS</span>
            </div>
          <?php endif; ?>
        </div>
        <span class="cat-label"><?= htmlspecialchars($cat['title']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- HERO BANNER DA CATEGORIA -->
  <div class="category-hero-banner">
    <span class="cat-badge-tag">OFERTAS CASAS BAHIA</span>
    <h1 class="cat-hero-title"><?= htmlspecialchars($currentCategory['title']) ?></h1>
    <p class="cat-hero-sub"><?= htmlspecialchars($currentCategory['subtitle']) ?></p>
  </div>

  <!-- CONTAGEM DE RESULTADOS -->
  <div class="results-info-bar">
    <div class="results-count">Exibindo <strong><?= count($filteredProducts) ?> produtos</strong></div>
    <div style="font-size: 12px; color: #168019; font-weight: 700;">Desconto no Pix Ativo</div>
  </div>

  <!-- GRADE DE PRODUTOS DA CATEGORIA (EXATAMENTE 2 POR LINHA) -->
  <div class="category-products-grid">
    <?php foreach ($filteredProducts as $prod): ?>
      <?php 
        $pId = $prod['id'];
        $pImg = $productImages[$pId] ?? 'assets/images/product_aoc_main.png';
        $pImgExt = strtolower(pathinfo(parse_url($pImg, PHP_URL_PATH), PATHINFO_EXTENSION));
        $regP = floatval($prod['regular_price'] ?? 0);
        $pixP = floatval($prod['pix_price'] ?? 0);
        $disc = !empty($prod['discount_percentage']) ? intval($prod['discount_percentage']) : 13;
        $instVal = !empty($prod['installment_value']) ? number_format($prod['installment_value'], 2, ',', '.') : number_format($regP / 10, 2, ',', '.');
        $maxInst = !empty($prod['max_installments']) ? intval($prod['max_installments']) : 10;
      ?>
      <a href="index.php?id=<?= $pId ?>" class="product-card-cb">
        <div class="card-badge-animated">
          CASAS BAHIA
        </div>

        <div class="card-img-wrapper">
          <?php if (in_array($pImgExt, ['mp4', 'webm', 'mov'])): ?>
            <video src="<?= htmlspecialchars($pImg) ?>" autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: contain;"></video>
          <?php else: ?>
            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
          <?php endif; ?>
        </div>

        <div class="card-product-title"><?= htmlspecialchars($prod['title']) ?></div>

        <div class="card-stars-row">
          <span style="display: flex; align-items: center; gap: 2px;">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
          </span>
          <span class="card-stars-count">(<?= intval($prod['reviews_count'] ?? 240) ?>)</span>
        </div>

        <div class="card-price-old-line">
          R$ <?= number_format($regP, 2, ',', '.') ?>
          <span class="card-discount-tag">-<?= $disc ?>%</span>
        </div>

        <div class="card-installment-txt">
          R$ <?= number_format($pixP, 2, ',', '.') ?> ou em até <?= $maxInst ?>x
        </div>

        <div class="card-price-pix-bold">R$ <?= number_format($pixP, 2, ',', '.') ?></div>

        <div class="card-badge-frete">FRETE GRÁTIS</div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- RODAPÉ DA CATEGORIA -->
  <footer class="cb-footer-container">
    <p>© 2026 Casas Bahia - Todos os direitos reservados.</p>
    <p style="margin-top: 4px; font-size: 11px;">Preços e condições exclusivos para compra online.</p>
  </footer>

</body>
</html>
