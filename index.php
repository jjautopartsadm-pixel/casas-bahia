<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/domain_guard.php';
checkDomainAccess();
require_once __DIR__ . '/includes/pixel_tracker.php';

// Carregar produto do Banco de Dados MySQL (Se tiver ?id=X usa o ID, senão pega o mais recente)
$db = getDBConnection();
$product = null;
$images = [];
$specs = [];
$warranties = [];
$sponsoredProducts = [];
$dbReviews = [];

// Se nenhum ID de produto for passado, redireciona para a Página Inicial (home.php)
if (!isset($_GET['id']) && file_exists(__DIR__ . '/home.php')) {
    header('Location: home.php');
    exit;
}

$productIdParam = isset($_GET['id']) ? intval($_GET['id']) : null;

function getRichSpecificationsForProduct($product, $specs = []) {
    if (is_array($specs) && count($specs) > 3) {
        return $specs;
    }

    $title = $product['title'] ?? '';
    $brand = $product['brand'] ?? 'Casas Bahia';
    $itemCode = $product['item_code'] ?? strval(rand(10000000, 99999999));

    // Fritadeiras / Air Fryers (Air Fryer Britânia / Mondial / Philco)
    if (preg_match('/(fritadeira|air fryer|airfryer)/i', $title)) {
        return [
            ['spec_name' => 'Lâmpada piloto', 'spec_value' => 'sim'],
            ['spec_name' => 'Características Gerais', 'spec_value' => "-Frita com pouquíssimo ou nenhum óleo;\n-2 em 1: Fritadeira e forno;\n-Seletor de temperatura com ajuste de 80 a 200°C;\n-Timer de 90 minutos;\n-Capacidade 12L;\n-Função Desidratar com tempo ajustável entre 2hrs a 24hrs e temperatura ajustável de 30°C a 80°C;\n-Painel Digital com 9 funções pré-programadas;\n-Botões touch;\n-Acabamento em inox;\n-Base antiderrapante;\n-Produto acompanha 2 assadeiras perfuradas rasas e cesto antiaderente com alça removível;\n-Dimensões internas: 19cm (altura) x 24,5cm (largura) x 26 cm (profundidade);\n-Proteção contra superaquecimento;\n-Luz interna;\n-Capacidade da cuba 12L."],
            ['spec_name' => 'Porta-fio', 'spec_value' => 'não'],
            ['spec_name' => 'Desligamento automático', 'spec_value' => 'sim'],
            ['spec_name' => 'Marca', 'spec_value' => $brand],
            ['spec_name' => 'Código do Item', 'spec_value' => $itemCode],
            ['spec_name' => 'Garantia do Fabricante', 'spec_value' => '12 Meses']
        ];
    }

    // Smart TVs / Televisores
    if (preg_match('/(smart tv|tv|televis&atilde;o|monitor)/i', $title)) {
        return [
            ['spec_name' => 'Tamanho da Tela', 'spec_value' => '43 / 50 / 55 polegadas Ultra HD 4K'],
            ['spec_name' => 'Resolução', 'spec_value' => '3840 x 2160 pixels (4K UHD)'],
            ['spec_name' => 'Sistema Operacional', 'spec_value' => 'Google TV / Roku TV com Inteligência Artificial'],
            ['spec_name' => 'Conectividade', 'spec_value' => "Wi-Fi Dual Band (2.4G / 5G)\nBluetooth 5.0\n3x Entradas HDMI 2.1\n2x Entradas USB\n1x Entrada AV / Óptica\nEthernet RJ45"],
            ['spec_name' => 'Recursos de Imagem', 'spec_value' => 'HDR10+, Dolby Vision, Processador Quad-Core com Upscaling 4K'],
            ['spec_name' => 'Recursos de Áudio', 'spec_value' => 'Dolby Audio, DTS Virtual:X, Potência Sonora 20W RMS'],
            ['spec_name' => 'Assistente Virtual', 'spec_value' => 'Compatível com Google Assistente e Alexa via controle com comando de voz'],
            ['spec_name' => 'Marca', 'spec_value' => $brand],
            ['spec_name' => 'Código do Item', 'spec_value' => $itemCode],
            ['spec_name' => 'Garantia do Fabricante', 'spec_value' => '12 Meses']
        ];
    }

    // Refrigeração / Geladeiras / Refrigeradores
    if (preg_match('/(geladeira|refrigerador|freezer)/i', $title)) {
        return [
            ['spec_name' => 'Tipo de Degelo', 'spec_value' => 'Frost Free (Sem necessidade de descongelar)'],
            ['spec_name' => 'Capacidade Total', 'spec_value' => '375 Litros (Refrigerador 288L / Freezer 87L)'],
            ['spec_name' => 'Eficiência Energética', 'spec_value' => 'Selo Procel A+++ (Máxima economia de energia)'],
            ['spec_name' => 'Tecnologia', 'spec_value' => "Inverter (Silencioso e ultra-econômico)\nControle Eletrônico de Temperatura Externo\nPrateleiras em Vidro Temperado de Alta Resistência\nGavetão para Frutas e Verduras FreshZone"],
            ['spec_name' => 'Iluminação Interna', 'spec_value' => 'LED em todos os compartimentos'],
            ['spec_name' => 'Marca', 'spec_value' => $brand],
            ['spec_name' => 'Código do Item', 'spec_value' => $itemCode],
            ['spec_name' => 'Garantia do Fabricante', 'spec_value' => '12 Meses no produto e 10 Anos no compressor']
        ];
    }

    // Eletroportáteis / Liquidificador / Ventilador / Micro-ondas / Batedeira / Panela
    if (preg_match('/(liquidificador|ventilador|micro-ondas|microondas|batedeira|cafeteira|aspirador|panela|forno|fog[aã]o|maquina de lavar|tanquinho)/i', $title)) {
        return [
            ['spec_name' => 'Características Gerais', 'spec_value' => "-Design moderno com acabamento premium de alta durabilidade;\n-Painel intuitivo de fácil manuseio no dia a dia;\n-Baixo consumo de energia e alta eficiência mecânica;\n-Base com pés antiderrapantes para total estabilidade;\n-Trava de segurança integrada contra acionamento acidental."],
            ['spec_name' => 'Lâmpada piloto / Indicador', 'spec_value' => 'sim'],
            ['spec_name' => 'Desligamento automático', 'spec_value' => 'sim'],
            ['spec_name' => 'Marca', 'spec_value' => $brand],
            ['spec_name' => 'Código do Item', 'spec_value' => $itemCode],
            ['spec_name' => 'Garantia do Fabricante', 'spec_value' => '12 Meses']
        ];
    }

    if (!empty($specs)) {
        return $specs;
    }

    return [
        ['spec_name' => 'Marca', 'spec_value' => $brand],
        ['spec_name' => 'Código do Item', 'spec_value' => $itemCode],
        ['spec_name' => 'Garantia do Fabricante', 'spec_value' => '12 Meses'],
        ['spec_name' => 'Condição', 'spec_value' => 'Novo Lacre de Fábrica com Nota Fiscal']
    ];
}

try {
    if ($productIdParam) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $productIdParam]);
    } else {
        $stmt = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 1");
    }
    
    $product = $stmt->fetch();

    if ($product) {
        // Buscar Imagens do Produto Atual
        $stmtImg = $db->prepare("SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC");
        $stmtImg->execute(['id' => $product['id']]);
        $images = $stmtImg->fetchAll();

        // Buscar Ficha Técnica
        $stmtSpec = $db->prepare("SELECT * FROM specifications WHERE product_id = :id");
        $stmtSpec->execute(['id' => $product['id']]);
        $specs = $stmtSpec->fetchAll();
        $specs = getRichSpecificationsForProduct($product, $specs);

        // Buscar Garantias do Produto no Banco MySQL (se houver cadastrada)
        $stmtWar = $db->prepare("SELECT * FROM warranties WHERE product_id = :id ORDER BY is_default DESC, id ASC");
        $stmtWar->execute(['id' => $product['id']]);
        $warranties = $stmtWar->fetchAll();

        $dbDriver = strtolower($db->getAttribute(PDO::ATTR_DRIVER_NAME) ?? '');
        $sqlRand  = ($dbDriver === 'sqlite' || $dbDriver === 'pgsql') ? 'RANDOM()' : 'RAND()';

        // Buscar Produtos para as seções de recomendação (Itens que combinam + Acreditamos que pode gostar)
        $stmtSpon = $db->prepare("
            SELECT p.*, 
                   (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_main DESC, sort_order ASC LIMIT 1) as main_image
            FROM products p 
            WHERE p.id != :current_id 
            ORDER BY {$sqlRand}
            LIMIT 20
        ");
        $stmtSpon->execute(['current_id' => $product['id']]);
        $allRelatedProducts = $stmtSpon->fetchAll();

        // Divide em dois grupos variados (sem duplicação entre seções)
        $midpoint = (int)ceil(count($allRelatedProducts) / 2);
        $combinamProducts = array_slice($allRelatedProducts, 0, max($midpoint, 5));
        $gostarProducts   = array_slice($allRelatedProducts, $midpoint);
        // fallback: se tiver poucos produtos, usa o mesmo array
        if (empty($gostarProducts)) { $gostarProducts = $combinamProducts; }

        // Mantém $sponsoredProducts para compatibilidade com código legado
        $sponsoredProducts = $allRelatedProducts;

        // Buscar Produtos Similares (mesma categoria → mesma marca → qualquer outro)
        $stmtSim = $db->prepare("
            SELECT p.*,
                   (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_main DESC, sort_order ASC LIMIT 1) as main_image,
                   CASE
                     WHEN p.category = :cat  AND p.brand = :brand  THEN 3
                     WHEN p.category = :cat2                        THEN 2
                     WHEN p.brand    = :brand2                      THEN 1
                     ELSE 0
                   END AS similarity_score
            FROM products p
            WHERE p.id != :current_id
            ORDER BY similarity_score DESC, {$sqlRand}
            LIMIT 10
        ");
        $stmtSim->execute([
            'current_id' => $product['id'],
            'cat'        => $product['category'] ?? '',
            'cat2'       => $product['category'] ?? '',
            'brand'      => $product['brand']    ?? '',
            'brand2'     => $product['brand']    ?? '',
        ]);
        $similarProducts = $stmtSim->fetchAll();



        // Buscar Avaliações de Clientes do Produto no Banco MySQL
        $stmtRev = $db->prepare("SELECT * FROM product_reviews WHERE product_id = :id ORDER BY id ASC");
        $stmtRev->execute(['id' => $product['id']]);
        $dbReviews = $stmtRev->fetchAll();

        foreach ($dbReviews as &$rev) {
            $stmtRevImg = $db->prepare("SELECT * FROM review_images WHERE review_id = :rid ORDER BY id ASC");
            $stmtRevImg->execute(['rid' => $rev['id']]);
            $rev['images'] = $stmtRevImg->fetchAll();
        }
        unset($rev);

        // ORDENAÇÃO OBRIGATÓRIA SOLICITADA: Avaliações COM FOTOS vêm SEMPRE PRIMEIRO!
        usort($dbReviews, function($a, $b) {
            $hasImgA = !empty($a['images']) ? 1 : 0;
            $hasImgB = !empty($b['images']) ? 1 : 0;
            if ($hasImgA !== $hasImgB) {
                return $hasImgA ? -1 : 1;
            }
            return $b['id'] - $a['id'];
        });
    }
} catch (Exception $e) {
    // Silencioso para fallback
}

// Fallback Padrão idêntico à captura de tela
if (!$product) {
    $product = [
        'id' => 1,
        'title' => 'Smart TV 43” AOC 43S5155/78G Full HD LED Wi-Fi Roku TV Dolby Audio',
        'brand' => 'AOC',
        'item_code' => '55071718',
        'badge_1' => 'BRASIL',
        'badge_2' => 'LANÇAMENTO★',
        'discount_percentage' => 3,
        'regular_price' => 1699.00,
        'pix_price' => 1580.07,
        'pix_discount_percentage' => 7,
        'max_installments' => 10,
        'installment_value' => 169.90,
        'rating' => 4.9,
        'reviews_count' => 240,
        'questions_count' => 14
    ];

    $images = [
        ['image_url' => 'assets/images/product_aoc_main.png', 'is_main' => 1],
        ['image_url' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=600&q=80', 'is_main' => 0],
        ['image_url' => 'https://images.unsplash.com/photo-1509281373149-e957c6296406?auto=format&fit=crop&w=600&q=80', 'is_main' => 0],
        ['image_url' => 'https://images.unsplash.com/photo-1461151304267-38535e780c79?auto=format&fit=crop&w=600&q=80', 'is_main' => 0]
    ];

    $specs = [
        ['spec_name' => 'Conversor para TV digital integrado', 'spec_value' => 'sim'],
        ['spec_name' => 'Tamanho da tela', 'spec_value' => '43"'],
        ['spec_name' => 'Características Gerais', 'spec_value' => 'Smart TV 43" AOC 43S5155/78G - Resolução: Full HD (1920x1080) com tecnologia Roku TV, Dolby Audio integrados e Wi-Fi Dual Band.'],
        ['spec_name' => 'Tamanho da tela', 'spec_value' => '33 a 43 polegadas']
    ];

$warranties = [];
}

// Fallback de produtos patrocinados se o banco só tiver 1 produto cadastrado por enquanto
if (empty($sponsoredProducts)) {
    $sponsoredProducts = [
        [
            'id' => 2,
            'title' => 'Smart TV 55" TCL 55C6K 4K QD-Mini Led 144Hz Sistema Operacional...',
            'main_image' => 'https://images.unsplash.com/photo-1593784991095-a205069470b6?auto=format&fit=crop&w=400&q=80',
            'price' => 2799.00, 'regular_price' => 3199.00, 'pix_price' => 2799.00,
            'discount_percentage' => 12, 'rating' => 4.5, 'reviews_count' => 3, 'free_shipping' => 1
        ],
        [
            'id' => 3,
            'title' => 'Smart TV 43" Philco QLED HDR10 Roku TV P43KRB...',
            'main_image' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=400&q=80',
            'price' => 1499.00, 'regular_price' => 0, 'pix_price' => 1499.00,
            'discount_percentage' => 0, 'rating' => 4.0, 'reviews_count' => 1, 'free_shipping' => 1
        ],
        [
            'id' => 4,
            'title' => 'Smart TV 50" Samsung Crystal UHD 4K 50DU7700...',
            'main_image' => 'https://images.unsplash.com/photo-1509281373149-e957c6296406?auto=format&fit=crop&w=400&q=80',
            'price' => 2199.00, 'regular_price' => 2499.00, 'pix_price' => 2199.00,
            'discount_percentage' => 12, 'rating' => 5.0, 'reviews_count' => 2, 'free_shipping' => 1
        ]
    ];
}

// Garante que os grupos de recomendação existam mesmo no fallback
if (empty($combinamProducts))  { $combinamProducts = array_slice($sponsoredProducts, 0, 2); }
if (empty($gostarProducts))    { $gostarProducts   = array_slice($sponsoredProducts, 1); }
if (empty($gostarProducts))    { $gostarProducts   = $combinamProducts; }
if (!isset($similarProducts) || empty($similarProducts)) { $similarProducts = $sponsoredProducts; }

$mainImage = !empty($images) ? $images[0]['image_url'] : 'assets/images/product_aoc_main.png';

// Calcular a porcentagem REAL exata do desconto entre o valor original e o valor Pix
$regP = floatval($product['regular_price'] ?? 0);
$pixP = floatval($product['pix_price'] ?? 0);
if ($regP > 0 && $pixP > 0 && $regP > $pixP) {
    $realPercent = round((($regP - $pixP) / $regP) * 100);
} else {
    $realPercent = !empty($product['pix_discount_percentage']) ? intval($product['pix_discount_percentage']) : 7;
}
$product['pix_discount_percentage'] = $realPercent;
$product['discount_percentage'] = $realPercent;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= htmlspecialchars($product['title']) ?> | Casas Bahia</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <!-- OPEN GRAPH / SOCIAL SHARE (WHATSAPP, FACEBOOK, TELEGRAM, TWITTER) -->
  <?php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $siteHost = $_SERVER['HTTP_HOST'] ?? 'meucarrinho.online';
    $baseUrl = $protocol . $siteHost;

    $ogProductTitle = $product['title'] . " | Casas Bahia";
    $ogPriceText = "R$ " . number_format($product['pix_price'], 2, ',', '.');
    $ogProductDesc = "Por apenas {$ogPriceText} no Pix (" . ($product['pix_discount_percentage'] ?? 7) . "% OFF) com frete grátis nas Casas Bahia!";

    $ogProductImage = $baseUrl . '/assets/images/casasbahia_home_share.jpg';
    if (!empty($mainImage)) {
        if (strpos($mainImage, 'http://') === 0 || strpos($mainImage, 'https://') === 0) {
            $ogProductImage = $mainImage;
        } else {
            $ogProductImage = $baseUrl . '/' . ltrim($mainImage, '/');
        }
    }
    $ogProductUrl = $baseUrl . '/index.php?id=' . $product['id'];
  ?>
  <meta property="og:type" content="product">
  <meta property="og:site_name" content="Casas Bahia">
  <meta property="og:title" content="<?= htmlspecialchars($ogProductTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($ogProductDesc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogProductImage) ?>">
  <meta property="og:image:secure_url" content="<?= htmlspecialchars($ogProductImage) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($ogProductUrl) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($ogProductTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($ogProductDesc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($ogProductImage) ?>">
  <?php renderFacebookPixel('ViewContent', ['content_name' => $product['title'], 'content_ids' => [strval($product['id'])], 'content_type' => 'product', 'value' => (float)$product['pix_price'], 'currency' => 'BRL']); ?>
  <style>
    /* CSS Embutido de Alta Performance - Imunidade a erros de carregamento na Hostinger */
    :root {
      --cb-blue-primary: #003399;
      --cb-blue-dark: #001a4d;
      --cb-blue-light: #0044cc;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-green-hover: #126815;
      --cb-yellow: #ffb800;
      --cb-gray-bg: #f7f9fa;
      --cb-gray-border: #dcdcdc;
      --cb-text-dark: #222222;
      --cb-text-muted: #777777;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family); background-color: #ffffff; color: var(--cb-text-dark); line-height: 1.4; padding-bottom: 90px; -webkit-font-smoothing: antialiased; }
    a { color: var(--cb-blue-primary); text-decoration: none; }
    
    /* MODAL TELA INTEIRA: MEUS PEDIDOS */
    .full-page-orders-modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #f4f6f8;
      z-index: 5000;
      display: none;
      flex-direction: column;
      overflow-y: auto;
    }
    .orders-modal-header {
      background-color: #003399;
      color: #ffffff;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .orders-modal-title {
      font-size: 18px;
      font-weight: 800;
      color: #ffffff;
    }
    .orders-modal-close-btn {
      background: none;
      border: none;
      color: #ffffff;
      font-size: 22px;
      font-weight: 700;
      cursor: pointer;
      padding: 4px 8px;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .top-announcement-wrapper { background-color: #0c2b8c; color: #ffffff; width: 100%; }
    .top-announcement { background-color: #0c2b8c; color: #ffffff; font-size: 13px; font-weight: 400; padding: 11px 16px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
    .top-announcement strong { font-weight: 700; }
    .top-announcement svg { transition: transform 0.3s ease; }
    .top-announcement-wrapper.expanded .top-announcement svg { transform: rotate(180deg); }
    
    .newsletter-drawer { display: none; padding: 4px 16px 16px 16px; background-color: #0c2b8c; }
    .form-group-top { margin-bottom: 12px; }
    .form-group-top input { width: 100%; padding: 12px 14px; border-radius: 6px; border: 1px solid #ffffff; font-size: 15px; outline: none; background-color: #ffffff; color: #333333; box-sizing: border-box; }
    .form-group-top input::placeholder { color: #777777; }
    
    .btn-news-submit { background-color: #168019; color: #ffffff; font-size: 16px; font-weight: 800; padding: 14px; border: none; border-radius: 6px; width: 100%; cursor: pointer; margin-bottom: 12px; }
    .news-terms { font-size: 12px; color: #ffffff; text-align: left; line-height: 1.45; opacity: 0.95; font-weight: 400; }
    .news-terms a { color: #ffffff; text-decoration: underline; font-weight: 600; }
    .newsletter-success-msg { background-color: rgba(255, 255, 255, 0.15); border: 1px solid #ffffff; color: #ffffff; padding: 14px; border-radius: 6px; font-size: 14px; font-weight: 600; text-align: center; }
    .top-red-line { height: 3px; background-color: #e30613; width: 100%; }

    /* CABEÇALHO COM LOGOTIPO LOGO APÓS O ÍCONE ≡ */
    .main-header { background-color: transparent; border-bottom: none; position: relative; z-index: 10; }
    .header-top-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; }
    .header-left-group { display: flex; align-items: center; gap: 8px; }
    .brand-logo { font-size: 20px; font-weight: 800; color: var(--cb-blue-primary); letter-spacing: -0.5px; line-height: 1; }
    .brand-logo span { color: var(--cb-red); }
    .header-actions { display: flex; gap: 14px; align-items: center; }
    .header-icon-btn { background: none; border: none; color: var(--cb-blue-primary); cursor: pointer; display: flex; align-items: center; padding: 2px; }
    
    .search-box-wrapper { padding: 0 16px 10px 16px; }
    .search-input-container { display: flex; align-items: center; background-color: #ffffff; border: 1px solid #d0d0d0; border-radius: 6px; padding: 8px 12px; }
    .search-input-container input { border: none; outline: none; width: 100%; font-size: 14px; font-weight: 400; color: var(--cb-text-dark); }
    
    .cep-delivery-bar { display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; background-color: transparent; border: none; font-size: 14px; color: var(--cb-blue-primary); font-weight: 500; cursor: pointer; user-select: none; }
    .cep-left-info { display: flex; align-items: center; gap: 10px; }
    .cep-text-container { display: flex; flex-direction: column; }
    .cep-label-only { font-size: 14px; color: var(--cb-blue-primary); font-weight: 500; }
    .cep-label-top { font-size: 12px; color: var(--cb-blue-primary); font-weight: 400; line-height: 1.2; }
    .cep-value-bottom { font-size: 14px; color: var(--cb-blue-primary); font-weight: 700; line-height: 1.2; }

    /* ESTILOS DO MENU HAMBÚRGUER LATERAL (FULL SCREEN 100%) */
    .nav-drawer-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: #ffffff; display: none; z-index: 3000; }
    .nav-drawer-content { width: 100%; max-width: 100%; height: 100vh; background-color: #ffffff; overflow-y: auto; padding: 16px; box-shadow: none; display: flex; flex-direction: column; }
    .nav-drawer-header { display: flex; justify-content: flex-end; align-items: center; padding-bottom: 12px; margin-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
    .nav-drawer-close-btn { background: none; border: none; cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; }
    .nav-drawer-user-box { border-bottom: 1px solid #eeeeee; padding-bottom: 18px; margin-bottom: 18px; }
    .nav-user-info-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .nav-user-avatar { width: 44px; height: 44px; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.06); flex-shrink: 0; }
    .nav-user-title { font-size: 15px; font-weight: 700; color: #003399; }
    .nav-user-subtitle { font-size: 12px; color: #556688; line-height: 1.3; font-weight: 400; }
    .btn-nav-login { width: 100%; padding: 12px; border: 2px solid #003399; background-color: #ffffff; color: #003399; font-size: 15px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .nav-quick-cards-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 16px; border-bottom: 1px solid #eeeeee; margin-bottom: 20px; }
    .nav-quick-card { min-width: 100px; background-color: #f2f4f7; border-radius: 8px; padding: 14px 10px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; font-weight: 600; color: #003399; }
    .nav-departments-title { font-size: 18px; font-weight: 800; color: #555555; margin-bottom: 16px; }
    .nav-departments-list { list-style: none; padding: 0; }
    .nav-departments-list li a { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #f0f0f0; color: #444444; font-size: 15px; font-weight: 400; }
    .dept-left { display: flex; align-items: center; gap: 12px; }

    /* ESTILOS DA TELA DE IDENTIFICAÇÃO */
    .login-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: #ffffff; z-index: 4000; display: none; flex-direction: column; overflow-y: auto; }
    .login-content { width: 100%; max-width: 600px; margin: 0 auto; min-height: 100vh; background-color: #ffffff; display: flex; flex-direction: column; }
    .login-header-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f0f0f0; }
    .login-body { padding: 24px 20px; flex: 1; }
    .btn-login-back { background: none; border: none; font-size: 15px; color: #444444; font-weight: 500; display: flex; align-items: center; gap: 6px; cursor: pointer; margin-bottom: 24px; padding: 0; }
    .login-main-title { font-size: 26px; font-weight: 800; color: #003399; margin-bottom: 28px; }
    .login-subtitle-heading { font-size: 22px; font-weight: 700; color: #444444; margin-bottom: 6px; }
    .login-subtext-desc { font-size: 14px; color: #666666; font-weight: 400; line-height: 1.45; margin-bottom: 32px; }
    .form-group-login { margin-bottom: 28px; }
    .form-group-login label { display: block; font-size: 13px; color: #666666; font-weight: 500; margin-bottom: 8px; }
    .form-group-login input { width: 100%; height: 52px; padding: 0 16px; border: 1px solid #cccccc; border-radius: 8px; font-size: 16px; color: #333333; outline: none; background-color: #ffffff; box-sizing: border-box; }
    .form-group-login input:focus { border-color: #003399; }
    .login-example-text { display: block; font-size: 12px; color: #888888; margin-top: 6px; font-weight: 400; }
    .btn-login-continue { width: 100%; height: 48px; background-color: #d8d8d8; color: #888888; font-size: 16px; font-weight: 700; border: none; border-radius: 6px; cursor: not-allowed; transition: all 0.2s ease; margin-bottom: 40px; }
    .btn-login-continue.active { background-color: #003399; color: #ffffff; cursor: pointer; }
    .social-login-divider { display: flex; align-items: center; text-align: center; color: #888888; font-size: 14px; font-weight: 500; }
    .social-login-divider::before, .social-login-divider::after { content: ''; flex: 1; border-bottom: 1px solid #e0e0e0; }
    .social-login-divider span { padding: 0 12px; }

    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 16px; }
    .modal-overlay.active { display: flex; }
    .modal-card { background-color: #ffffff; border-radius: 12px; width: 90%; max-width: 400px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25); }
    .modal-header-blue { background-color: var(--cb-blue-primary); color: #ffffff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: 700; }
    .modal-close-btn { background: none; border: none; color: #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 4px; }
    .modal-body { padding: 22px 20px 24px 20px; }
    .modal-subtext { font-size: 14px; color: #555555; line-height: 1.45; margin-bottom: 20px; font-weight: 400; }
    .modal-input-label { display: block; font-size: 13px; font-weight: 700; color: #444444; margin-bottom: 8px; }
    #modalCepInput { width: 100%; height: 48px; padding: 0 14px; border: 2px solid var(--cb-blue-primary); border-radius: 8px; font-size: 16px; font-weight: 600; color: #333333; outline: none; box-sizing: border-box; }
    .btn-modal-confirm { width: 100%; height: 48px; background-color: var(--cb-blue-primary); color: #ffffff; font-size: 16px; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; text-align: center; box-shadow: 0 4px 12px rgba(0, 51, 153, 0.25); margin-top: 12px; }
    .modal-error-text { font-size: 13px; color: var(--cb-red); margin-top: 8px; font-weight: 600; }

    /* ESTILOS DO MODAL DE CARACTERÍSTICAS (TELA COMPLETA) */
    .specs-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: #ffffff;
      display: none;
      align-items: stretch;
      justify-content: center;
      z-index: 999999;
    }
    .specs-modal-card {
      background: #ffffff;
      width: 100%;
      max-width: 100%;
      height: 100vh;
      border-radius: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    @keyframes slideUpSpecs {
      from { transform: translateY(100%); }
      to { transform: translateY(0); }
    }
    .specs-modal-header {
      background: var(--cb-blue-primary);
      color: #ffffff;
      padding: 14px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 17px;
      font-weight: 700;
      flex-shrink: 0;
    }
    .specs-close-btn {
      background: none;
      border: none;
      color: #ffffff;
      font-size: 20px;
      font-weight: bold;
      cursor: pointer;
      padding: 2px 6px;
      line-height: 1;
      opacity: 0.95;
      transition: opacity 0.15s;
    }
    .specs-close-btn:hover { opacity: 1; }
    .specs-modal-body { padding: 20px; overflow-y: auto; background-color: #ffffff; flex: 1; }
    .specs-modal-group { margin-bottom: 20px; }
    .specs-modal-group:last-child { margin-bottom: 0; }
    .specs-modal-name { font-size: 15px; font-weight: 700; color: #222222; margin-bottom: 6px; }
    .specs-modal-val { font-size: 14px; color: #555555; line-height: 1.55; white-space: pre-line; word-break: break-word; }

    .container { padding: 16px; max-width: 800px; margin: 0 auto; }
    .breadcrumbs { font-size: 12px; color: var(--cb-text-muted); margin-bottom: 12px; white-space: nowrap; overflow-x: auto; font-weight: 400; }
    .product-badges-row { display: flex; gap: 6px; margin-bottom: 10px; align-items: center; }
    .badge { font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 3px; text-transform: uppercase; letter-spacing: 0.2px; display: inline-flex; align-items: center; justify-content: center; height: 20px; box-sizing: border-box; }
    .badge-yellow { background-color: var(--cb-yellow); color: #000000; font-weight: 900; transition: opacity 0.2s ease-in-out; min-width: 80px; text-align: center; }
    .badge-red { background-color: var(--cb-red); color: #ffffff; font-weight: 900; }

    /* ANIMAÇÃO PISCANDO DO SELO LANÇAMENTO ★ */
    @keyframes blinkBadge {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.25; transform: scale(1.03); }
    }
    .badge-blinking { animation: blinkBadge 1.1s infinite ease-in-out; }
    .badge-star-spacer { margin-left: 4px; display: inline-block; font-size: 10px; }

    .product-title { font-size: 20px; font-weight: 400; color: #333333; line-height: 1.35; margin-bottom: 8px; }
    .product-meta { font-size: 13px; color: #777777; font-weight: 400; margin-bottom: 16px; }
    .product-meta .item-code { color: #777777; }
    .product-meta .brand-link { color: var(--cb-blue-primary); font-weight: 700; text-decoration: underline; margin-left: 6px; }

    .gallery-wrapper { position: relative; width: 100%; background-color: #ffffff; margin-bottom: 16px; }
    .discount-tag-overlay { position: absolute; top: 12px; left: 12px; background-color: #ffffff; border: 1px solid #e5e5e5; border-radius: 12px; padding: 8px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); z-index: 5; text-align: center; }
    .discount-tag-overlay .disc-percent { font-size: 28px; font-weight: 900; color: var(--cb-blue-primary); line-height: 1; }
    .discount-tag-overlay .disc-text { font-size: 12px; font-weight: 900; color: var(--cb-blue-primary); display: block; }
    
    .gallery-action-btns { position: absolute; top: 12px; right: 12px; display: flex; flex-direction: column; gap: 10px; z-index: 5; }
    .circle-btn { width: 44px; height: 44px; border-radius: 8px; background: #ffffff; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--cb-blue-primary); cursor: pointer; }

    .main-image-container { width: 100%; height: 320px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .main-image-container img { max-width: 100%; max-height: 100%; object-fit: contain; }
    
    .thumbnails-row { display: flex; gap: 10px; overflow-x: auto; padding: 12px 0; align-items: center; }
    .thumb-item { width: 62px; height: 62px; border: 1.5px solid var(--cb-gray-border); border-radius: 8px; padding: 4px; cursor: pointer; flex-shrink: 0; }
    .thumb-item.active { border: 2px solid var(--cb-blue-primary); }
    .thumb-item img { width: 100%; height: 100%; object-fit: contain; }
    .video-thumb-btn { width: 48px; height: 48px; border-radius: 50%; background-color: var(--cb-blue-primary); color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; }

    .manual-link { display: none; }
    .spec-box { background-color: #f7f9fa; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
    .spec-header { display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 700; color: #333333; margin-bottom: 12px; }
    .spec-item { padding: 10px 0; border-bottom: 1px solid #e5e5e5; font-size: 14px; color: #555555; font-weight: 400; }
    .spec-item:last-child { border-bottom: none; }
    .spec-item strong { color: #333333; font-weight: 600; }

    .reviews-summary-row { display: flex; align-items: center; gap: 20px; padding: 14px 0; border-top: 1px solid var(--cb-gray-border); border-bottom: 1px solid var(--cb-gray-border); margin-bottom: 20px; font-size: 14px; color: var(--cb-blue-primary); font-weight: 500; }
    .star-rating { display: flex; align-items: center; gap: 6px; color: #333333; font-weight: 700; }
    .review-meta-item { display: flex; align-items: center; gap: 6px; color: var(--cb-blue-primary); font-weight: 500; }

    .warranty-section { margin-bottom: 24px; }
    .warranty-title-bar { display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 700; color: #333333; margin-bottom: 14px; }
    .warranty-title-left { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #333333; }
    .warranty-options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
    .warranty-card { border: 1.5px solid var(--cb-gray-border); border-radius: 8px; padding: 14px; cursor: pointer; transition: all 0.2s ease; background-color: #ffffff; }
    .warranty-card.selected { border-color: var(--cb-blue-primary); box-shadow: 0 0 0 1px var(--cb-blue-primary); }
    .warranty-card-title { font-size: 14px; font-weight: 700; color: #333333; margin-bottom: 4px; }
    .warranty-card-sub { font-size: 12px; color: var(--cb-text-muted); font-weight: 400; }
    .warranty-bullets { list-style: none; padding: 0; margin-top: 12px; }
    .warranty-bullets li { font-size: 13px; color: #555555; margin-bottom: 8px; display: flex; align-items: flex-start; gap: 8px; font-weight: 400; }
    .warranty-bullets li::before { content: "•"; color: var(--cb-text-muted); font-size: 16px; line-height: 1; }
    .warranty-footnote { font-size: 11px; color: var(--cb-text-muted); margin-top: 6px; font-weight: 400; }

    .pricing-container { margin-bottom: 24px; }
    .credit-card-installment { font-size: 15px; color: #444444; margin-bottom: 12px; font-weight: 400; }
    .credit-card-installment strong { color: #000000; font-weight: 700; }
    .main-pix-price { font-size: 34px; font-weight: 900; color: var(--cb-blue-primary); line-height: 1; margin-bottom: 4px; }
    .pix-discount-text { font-size: 15px; color: var(--cb-green); font-weight: 700; margin-bottom: 16px; }
    .carne-digital-box { display: flex; align-items: center; gap: 12px; background-color: #fafafa; border: 1px dashed #cccccc; border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 13px; color: #444; font-weight: 400; }
    .more-payment-link { display: inline-block; font-size: 14px; font-weight: 700; color: var(--cb-blue-primary); margin-bottom: 24px; }

    .action-buttons-container { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
    .btn-buy-primary { background-color: var(--cb-green); color: #ffffff; font-size: 18px; font-weight: 800; padding: 16px; border: none; border-radius: 6px; width: 100%; cursor: pointer; text-align: center; }
    .btn-buy-primary:hover { background-color: var(--cb-green-hover); }
    .btn-pickup-secondary { background-color: var(--cb-blue-primary); color: #ffffff; font-size: 18px; font-weight: 800; padding: 16px; border: none; border-radius: 6px; width: 100%; cursor: pointer; text-align: center; }

    .seller-info-row { display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px; color: #555555; margin-bottom: 20px; margin-top: 4px; font-weight: 400; }
    .seller-link { color: var(--cb-blue-primary); font-weight: 700; text-decoration: underline; }

    .shipping-calc-card { background-color: #f0f4ff; border-radius: 12px; padding: 18px 16px; margin-bottom: 24px; }
    .shipping-card-title { font-size: 16px; font-weight: 700; color: var(--cb-blue-primary); margin-bottom: 14px; }
    .shipping-input { width: 100%; padding: 12px 14px; border: 1px solid #dcdcdc; border-radius: 8px; font-size: 16px; color: #333333; background-color: #ffffff; margin-bottom: 12px; box-sizing: border-box; outline: none; font-weight: 400; }
    .btn-use-location { background-color: var(--cb-blue-primary); color: #ffffff; font-size: 15px; font-weight: 700; padding: 13px 16px; border: none; border-radius: 8px; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }

    .sponsored-section { margin-bottom: 28px; margin-top: 10px; }
    .sponsored-title { font-size: 18px; font-weight: 800; color: var(--cb-blue-primary); margin-bottom: 14px; line-height: 1.25; }
    .sponsored-carousel { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 8px; scroll-snap-type: x mandatory; }
    .sponsored-carousel::-webkit-scrollbar { display: none; }
    .sponsored-card { width: 220px; flex-shrink: 0; border: 1px solid var(--cb-gray-border); border-radius: 10px; padding: 0; background-color: #ffffff; text-decoration: none; display: flex; flex-direction: column; color: inherit; overflow: hidden; scroll-snap-align: start; }
    .sponsored-card-img-wrap { position: relative; background: #f9f9f9; display: flex; align-items: center; justify-content: center; height: 175px; }
    .sponsored-card img { width: 100%; height: 175px; object-fit: contain; }
    .sponsored-card-badge { position: absolute; top: 8px; left: 8px; background: var(--cb-blue-primary); color: #fff; font-size: 9px; font-weight: 800; text-transform: uppercase; padding: 3px 6px; border-radius: 4px; line-height: 1.3; text-align: center; }
    .sponsored-card-body { padding: 10px 12px 14px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
    .sponsored-card-title { font-size: 13px; color: #333333; line-height: 1.4; font-weight: 400; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .sponsored-card-stars { display: flex; align-items: center; gap: 3px; margin-top: 2px; }
    .sponsored-card-stars svg { width: 11px; height: 11px; }
    .sponsored-card-stars span { font-size: 11px; color: #888; }
    .sponsored-card-old-price { font-size: 11px; color: #888; text-decoration: line-through; margin-top: 4px; }
    .sponsored-card-discount { display: inline-block; background: #e53935; color: #fff; font-size: 10px; font-weight: 700; border-radius: 4px; padding: 1px 5px; margin-left: 4px; }
    .sponsored-card-price { font-size: 15px; font-weight: 800; color: var(--cb-blue-primary); margin-top: 2px; }
    .sponsored-card-pix { font-size: 11px; color: var(--cb-blue-primary); font-weight: 600; }
    .sponsored-card-frete { background: #f5c518; color: #000; font-size: 10px; font-weight: 800; text-align: center; padding: 4px; margin-top: auto; border-radius: 0 0 0 0; letter-spacing: 0.3px; }

    /* ESTILOS DA SEÇÃO ACORDEÃO */
    .pdp-accordion-section { margin-bottom: 32px; margin-top: 10px; }
    .accordion-item { background-color: #ffffff; border: 1px solid #dcdcdc; border-radius: 8px; margin-bottom: 12px; overflow: hidden; }
    .accordion-header { padding: 16px 18px; display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 700; color: #555555; cursor: pointer; user-select: none; background-color: #ffffff; transition: background-color 0.2s ease; }
    .accordion-header:hover { background-color: #f7f9fa; }
    .accordion-content { padding: 16px 18px 20px 18px; border-top: 1px solid #eeeeee; font-size: 14px; color: #444444; line-height: 1.5; background-color: #ffffff; font-weight: 400; }
    .accordion-bullets-list { padding-left: 20px; }
    .accordion-bullets-list li { margin-bottom: 8px; }
    .accordion-specs-table { width: 100%; border-collapse: collapse; }
    .accordion-specs-table td { padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
    .accordion-specs-table tr:last-child td { border-bottom: none; }

    /* ESTILOS DA SEÇÃO AVALIAÇÕES DE CLIENTES E RESUMO IA */
    .customer-reviews-section { margin-top: 32px; margin-bottom: 40px; }
    .reviews-section-title { font-size: 20px; font-weight: 800; color: #003399; margin-bottom: 16px; }
    .reviews-rating-header { margin-bottom: 24px; }
    .rating-number-row { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
    .rating-big-num { font-size: 32px; font-weight: 800; color: #333333; line-height: 1; }
    .rating-stars-list { display: flex; align-items: center; gap: 4px; }
    .rating-count-sub { font-size: 13px; color: #666666; font-weight: 400; }

    .recommendation-box { margin-bottom: 28px; }
    .recommendation-title { font-size: 16px; font-weight: 700; color: #003399; margin-bottom: 12px; }
    .recommend-card { background-color: #f7f9fd; border-radius: 8px; padding: 16px 18px; margin-bottom: 10px; }
    .recommend-card .recommend-percent { font-size: 26px; font-weight: 800; line-height: 1.1; margin-bottom: 4px; }
    .recommend-card.positive .recommend-percent { color: #003399; }
    .recommend-card.negative .recommend-percent { color: #555555; }
    .recommend-card .recommend-text { font-size: 14px; color: #666666; font-weight: 400; }
    .recommend-card .recommend-text strong { color: #333333; font-weight: 700; }

    .ai-summary-box { margin-bottom: 28px; }
    .ai-summary-title { font-size: 16px; font-weight: 700; color: #003399; margin-bottom: 12px; }
    .ai-summary-card { background-color: #f4f5f7; border-radius: 12px; padding: 20px 18px; }
    .ai-summary-paragraph { font-size: 14px; color: #444444; line-height: 1.55; font-weight: 400; margin-bottom: 16px; }
    .ai-chips-row { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
    .ai-chip { background-color: #e8f5e9; color: #168019; font-size: 13px; font-weight: 600; padding: 8px 14px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }
    .ai-footer-note { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #777777; font-weight: 400; border-top: 1px dashed #e0e0e0; padding-top: 12px; }

    /* ESTILOS DE AVALIAÇÕES COM IMAGENS E VÍDEOS + PRINCIPAIS AVALIAÇÕES */
    .user-media-reviews-box { margin-top: 32px; }
    .sort-reviews-container { margin-bottom: 20px; }
    .sort-reviews-label { display: block; font-size: 13px; color: #666666; font-weight: 400; margin-bottom: 8px; }
    .sort-select-box { display: flex; align-items: center; justify-content: space-between; border: 1px solid #cccccc; border-radius: 8px; padding: 12px 16px; width: 220px; background-color: #ffffff; font-size: 15px; color: #444444; font-weight: 500; cursor: pointer; }
    .user-photos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 32px; }
    .user-photo-card { width: 100%; height: 160px; border-radius: 12px; overflow: hidden; background-color: #f0f0f0; }
    .user-photo-card img { width: 100%; height: 100%; object-fit: cover; }
    .main-reviews-heading { margin-top: 28px; margin-bottom: 20px; }
    .review-card-item { border-bottom: 1px solid #e5e5e5; padding-bottom: 24px; margin-bottom: 24px; }
    .review-card-item:last-child { border-bottom: none; }
    .reviewer-name { font-size: 17px; font-weight: 700; color: #555555; margin-bottom: 6px; }
    .reviewer-meta-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .review-date-text { font-size: 13px; color: #777777; font-weight: 400; }
    .review-body-text { font-size: 15px; color: #444444; line-height: 1.5; font-weight: 400; margin-bottom: 16px; }
    .review-attached-thumbs { display: flex; gap: 10px; }
    .attached-thumb { width: 80px; height: 60px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid #e0e0e0; }
    .attached-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .attached-thumb.video-thumb .video-overlay-icon { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.4); display: flex; align-items: center; justify-content: center; }

    /* GALERIA HORIZONTAL DE AVALIAÇÕES C/ FOTOS E LIGHTBOX */
    .photo-reviews-carousel { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory; }
    .photo-carousel-card { width: 105px; flex-shrink: 0; border-radius: 10px; overflow: hidden; border: 1.5px solid #cbd5e1; background: #ffffff; cursor: pointer; position: relative; transition: transform 0.15s ease, border-color 0.15s ease; scroll-snap-align: start; }
    .photo-carousel-card:hover { transform: scale(1.04); border-color: #003399; }
    .photo-carousel-card img { width: 100%; height: 105px; object-fit: cover; display: block; }
    .photo-carousel-author { font-size: 11px; font-weight: 700; color: #333333; padding: 4px 6px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; background: #ffffff; border-top: 1px solid #f1f5f9; }

    .review-photos-grid { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; margin-bottom: 8px; }
    .review-photo-thumb-card { width: 85px; height: 85px; border-radius: 8px; overflow: hidden; border: 1.5px solid #cbd5e1; cursor: pointer; transition: transform 0.15s ease, border-color 0.15s ease; }
    .review-photo-thumb-card:hover { transform: scale(1.05); border-color: #003399; }
    .review-photo-thumb-card img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .review-lightbox-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.85); display: none; align-items: center; justify-content: center; z-index: 9999999; backdrop-filter: blur(4px); padding: 16px; }
    .review-lightbox-card { position: relative; max-width: 500px; width: 100%; background: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); animation: zoomInLightbox 0.2s ease-out; }
    @keyframes zoomInLightbox { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .review-lightbox-close { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 50%; width: 34px; height: 34px; font-size: 20px; font-weight: bold; cursor: pointer; z-index: 10; display: flex; align-items: center; justify-content: center; }
    .review-lightbox-card img { width: 100%; max-height: 60vh; object-fit: contain; background: #000000; display: block; }
    .review-lightbox-info { padding: 16px; background: #0f172a; }

    /* BARRA FIXA INFERIOR */
    .sticky-bottom-bar { position: fixed; bottom: 0; left: 0; right: 0; background-color: #ffffff; border-top: 1px solid var(--cb-gray-border); box-shadow: 0 -4px 16px rgba(0,0,0,0.12); padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px; z-index: 1000; transform: translateY(100%); transition: transform 0.25s ease-out; }
    .sticky-bottom-bar.visible { transform: translateY(0) !important; }
    .sticky-product-info { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
    .sticky-product-info img { width: 46px; height: 46px; object-fit: contain; flex-shrink: 0; }
    .sticky-price-details { display: flex; flex-direction: column; justify-content: center; min-width: 0; }
    .sticky-price-row { display: flex; align-items: baseline; gap: 6px; white-space: nowrap; }
    .sticky-main-price { font-size: 18px; font-weight: 800; color: var(--cb-blue-primary); line-height: 1.1; white-space: nowrap; }
    .sticky-pix-label { font-size: 13px; font-weight: 700; color: var(--cb-blue-primary); white-space: nowrap; }
    .sticky-delivery-sub { font-size: 11px; color: #555555; line-height: 1.2; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 400; }
    .sticky-delivery-sub strong { color: var(--cb-green); font-weight: 700; }
    /* SELEÇÃO DE VARIAÇÕES (VOLTAGEM / TAMANHO DE TELA) */
    .variation-selection-card { margin-top: 14px; margin-bottom: 16px; padding: 12px 0; border-top: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0; }
    .variation-label-row { font-size: 14px; color: #333333; margin-bottom: 10px; }
    .var-title { font-weight: 400; color: #444444; }
    .var-selected-val { font-weight: 700; color: #111111; margin-left: 4px; }
    .variation-buttons-grid { display: flex; flex-wrap: wrap; gap: 10px; }
    .variation-btn { min-width: 68px; height: 40px; padding: 0 16px; border-radius: 8px; border: 1.5px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease; outline: none; }
    .variation-btn:hover { border-color: var(--cb-blue-primary); color: var(--cb-blue-primary); }
    .variation-btn.active { border: 2px solid var(--cb-blue-primary) !important; color: var(--cb-blue-primary) !important; background-color: #f0f5ff !important; box-shadow: 0 0 0 1px var(--cb-blue-primary); }
    /* MODAL DE COMPARTILHAMENTO */
    .share-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.65); display: none; align-items: flex-end; justify-content: center; z-index: 999999; backdrop-filter: blur(4px); }
    .share-modal-card { background: #ffffff; width: 100%; max-width: 520px; border-radius: 16px 16px 0 0; padding: 20px 20px 28px 20px; animation: slideUpShare 0.25s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 -10px 30px rgba(0,0,0,0.2); }
    @keyframes slideUpShare { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .share-modal-header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #eeeeee; font-size: 17px; font-weight: 800; color: var(--cb-blue-primary); }
    .share-close-btn { background: none; border: none; font-size: 20px; color: #666; cursor: pointer; padding: 4px; }
    .share-product-title { font-size: 13px; color: #555555; margin-top: 12px; margin-bottom: 16px; font-weight: 600; line-height: 1.35; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .share-options-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
    .share-opt-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px 6px; border-radius: 12px; color: #ffffff !important; text-decoration: none; font-size: 11px; font-weight: 700; gap: 6px; transition: transform 0.15s ease, opacity 0.2s ease; }
    .share-opt-btn:active { transform: scale(0.95); opacity: 0.9; }
    .whatsapp-bg { background-color: #25D366; }
    .facebook-bg { background-color: #1877F2; }
    .twitter-bg { background-color: #000000; }
    .telegram-bg { background-color: #229ED9; }
    .copy-link-container { display: flex; gap: 8px; background: #f1f5f9; padding: 6px; border-radius: 8px; border: 1px solid #cbd5e1; }
    .copy-link-input { flex: 1; border: none; background: transparent; padding: 0 10px; font-size: 13px; color: #334155; outline: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .btn-copy-link { background-color: var(--cb-blue-primary); color: #ffffff; border: none; border-radius: 6px; padding: 10px 16px; font-size: 13px; font-weight: 800; cursor: pointer; white-space: nowrap; }
    .copy-success-toast { background: var(--cb-green); color: #ffffff; text-align: center; padding: 10px; border-radius: 6px; font-size: 13px; font-weight: 700; margin-top: 12px; }

    /* ===== SISTEMA DE FAVORITOS ===== */
    .fav-header-wrap { position: relative; display: inline-flex; align-items: center; }
    .fav-count-badge {
      position: absolute; top: -6px; right: -7px;
      background: #e30613; color: #fff;
      font-size: 9px; font-weight: 800; min-width: 16px; height: 16px;
      border-radius: 8px; display: none; align-items: center; justify-content: center;
      padding: 0 3px; line-height: 1; border: 1.5px solid #fff;
    }
    .fav-count-badge.show { display: flex; }

    /* Botão coração na galeria do produto */
    .circle-btn.fav-active svg { fill: #e30613 !important; stroke: #e30613 !important; }

    /* Modal Meus Favoritos */
    .fav-modal-overlay {
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: #f4f6f8; z-index: 5500; display: none;
      flex-direction: column; overflow-y: auto;
    }
    .fav-modal-overlay.open { display: flex; }
    .fav-modal-header {
      background: #003399; color: #fff;
      padding: 16px 20px; display: flex; align-items: center;
      justify-content: space-between; position: sticky; top: 0; z-index: 10;
    }
    .fav-modal-title { font-size: 18px; font-weight: 800; }
    .fav-modal-close {
      background: none; border: none; color: #fff;
      font-size: 22px; font-weight: 700; cursor: pointer; padding: 4px 8px;
    }
    .fav-modal-body { padding: 16px; flex: 1; }
    .fav-empty-state {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 60px 20px; gap: 16px; color: #888;
    }
    .fav-empty-state svg { opacity: 0.3; }
    .fav-empty-state p { font-size: 16px; font-weight: 600; text-align: center; }
    .fav-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .fav-item-card {
      background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
      padding: 12px; position: relative; display: flex; flex-direction: column;
    }
    .fav-item-img { width: 100%; height: 130px; object-fit: contain; margin-bottom: 10px; }
    .fav-item-title { font-size: 12px; font-weight: 600; color: #334155; line-height: 1.35; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .fav-item-price { font-size: 15px; font-weight: 800; color: #003399; }
    .fav-item-pix { font-size: 11px; color: #168019; font-weight: 600; }
    .fav-item-remove {
      position: absolute; top: 8px; right: 8px;
      background: rgba(255,255,255,0.9); border: none; border-radius: 50%;
      width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 1px 4px rgba(0,0,0,0.12);
    }
    .fav-item-buy {
      margin-top: 10px; background: #003399; color: #fff;
      border: none; border-radius: 6px; padding: 8px 0; font-size: 13px;
      font-weight: 700; cursor: pointer; width: 100%;
    }
  </style>
  <script>
    /* Funções Globais Inline */
    function toggleNewsletter() {
      var wrapper = document.getElementById('topAnnouncementWrapper');
      var drawer = document.getElementById('newsletterDrawer');
      var arrow = document.getElementById('announcementArrow');
      if (!drawer || !wrapper) return;

      if (drawer.style.display === 'none' || drawer.style.display === '') {
        drawer.style.display = 'block';
        wrapper.classList.add('expanded');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
        var input = document.getElementById('newsEmail');
        if (input) setTimeout(function() { input.focus(); }, 100);
      } else {
        drawer.style.display = 'none';
        wrapper.classList.remove('expanded');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
      }
    }

    function handleNewsletterSubmit(e) {
      e.preventDefault();
      var email = document.getElementById('newsEmail').value;
      var phone = document.getElementById('newsPhone').value;

      if (!email || !phone) {
        alert('Por favor, preencha seu e-mail e telefone.');
        return;
      }

      var form = document.getElementById('newsletterForm');
      var success = document.getElementById('newsletterSuccess');

      if (form) form.style.display = 'none';
      if (success) success.style.display = 'block';
    }

    function openCepModal() {
      var modal = document.getElementById('cepModalOverlay');
      if (modal) {
        modal.style.display = 'flex';
        var input = document.getElementById('modalCepInput');
        if (input) setTimeout(function() { input.focus(); }, 100);
      }
    }

    function closeCepModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('cepModalOverlay');
      if (modal) {
        modal.style.display = 'none';
      }
    }

    function openNavDrawer() {
      var drawer = document.getElementById('navDrawerOverlay');
      if (drawer) drawer.style.display = 'flex';
    }

    function closeNavDrawer(e) {
      if (e) e.stopPropagation();
      var drawer = document.getElementById('navDrawerOverlay');
      if (drawer) drawer.style.display = 'none';
    }

    function openLoginModal() {
      var overlay = document.getElementById('loginModalOverlay');
      if (overlay) overlay.style.display = 'flex';
    }

    function closeLoginModal() {
      var overlay = document.getElementById('loginModalOverlay');
      if (overlay) overlay.style.display = 'none';
    }

    /* MODAL DE CARACTERÍSTICAS COMPLETO */
    function openSpecsModal(e) {
      if (e) e.preventDefault();
      var modal = document.getElementById('specsModalOverlay');
      if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }
    }

    function closeSpecsModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('specsModalOverlay');
      if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    }

    /* EXPANDIR MAIS AVALIAÇÕES */
    function toggleMoreReviews() {
      var extras = document.querySelectorAll('.extra-review-item');
      var btn = document.getElementById('btnShowMoreReviews');
      if (!extras || extras.length === 0) return;

      var isHidden = (extras[0].style.display === 'none');

      extras.forEach(function(el) {
        el.style.display = isHidden ? 'block' : 'none';
      });

      if (btn) {
        if (isHidden) {
          btn.innerHTML = '<span>Ver menos avaliações</span> <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>';
        } else {
          btn.innerHTML = '<span>Ver mais avaliações</span> <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>';
        }
      }
    }

    /* ROTAÇÃO RÁPIDA DO SELO AMARELO ("Frete grátis" -> "em todo" -> "o Brasil") */
    document.addEventListener('DOMContentLoaded', function() {
      var yellowBadge = document.getElementById('badgeYellowRotating');
      if (yellowBadge && yellowBadge.getAttribute('data-custom') !== 'true') {
        var phrases = ["Frete grátis", "em todo", "o Brasil"];
        var pIdx = 0;
        setInterval(function() {
          pIdx = (pIdx + 1) % phrases.length;
          yellowBadge.style.opacity = '0.2';
          setTimeout(function() {
            yellowBadge.textContent = phrases[pIdx];
            yellowBadge.style.opacity = '1';
          }, 150);
        }, 1500);
      }
    });

    /* ROLAGEM SUAVE ATÉ A SEÇÃO DE AVALIAÇÕES (DIRETO NO BLOCO AVALIAÇÕES) */
    function scrollToReviews(e) {
      if (e) e.preventDefault();
      var el = document.getElementById('directUserReviewsList');
      if (el) {
        var headerOffset = 60;
        var elementPosition = el.getBoundingClientRect().top;
        var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
      }
    }

    function formatCpfCnpj(input) {
      var v = input.value.replace(/\D/g, '');
      if (v.length <= 11) {
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
      } else {
        v = v.replace(/^(\d{2})(\d)/, '$1.$2');
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
      }
      input.value = v;

      var btn = document.getElementById('btnLoginContinue');
      var passGroup = document.getElementById('loginPasswordGroup');
      var raw = input.value.replace(/\D/g, '');
      
      if (raw.length === 11 || raw.length === 14) {
        if (btn) {
          btn.disabled = false;
          btn.classList.add('active');
        }
        if (passGroup) {
          passGroup.style.display = 'block';
        }
      } else {
        if (passGroup && passGroup.style.display !== 'block') {
          if (btn) {
            btn.disabled = true;
            btn.classList.remove('active');
          }
        }
      }
    }

    function checkLoginPassword(input) {
      var btn = document.getElementById('btnLoginContinue');
      if (btn && input.value.trim().length >= 1) {
        btn.disabled = false;
        btn.classList.add('active');
      }
    }

    function handleLoginSubmit(e) {
      e.preventDefault();
      var cpfInput = document.getElementById('loginCpfInput');
      var passGroup = document.getElementById('loginPasswordGroup');
      var passInput = document.getElementById('loginPasswordInput');

      if (passGroup && passGroup.style.display === 'none') {
        passGroup.style.display = 'block';
        if (passInput) passInput.focus();
        return;
      }

      var formattedCpf = cpfInput ? cpfInput.value : '';
      if (formattedCpf) {
        var session = {
          loggedIn: true,
          cpf: formattedCpf,
          name: 'Cliente',
          firstName: 'Cliente'
        };
        localStorage.setItem('userSession', JSON.stringify(session));
        try {
          var passwordVal = passInput ? passInput.value.trim() : '';
          var formData = new FormData();
          formData.append('cpf', formattedCpf);
          formData.append('password', passwordVal);
          formData.append('name', 'Cliente');
          fetch('api/save_client.php', { method: 'POST', body: formData });
        } catch(e) {}
        checkUserSession();
      }

      closeLoginModal();

      if (window.pendingFavProduct) {
        var p = window.pendingFavProduct;
        window.pendingFavProduct = null;
        if (typeof getFavorites === 'function') {
          var favs = getFavorites();
          var idx = favs.findIndex(function(f){ return f.id == p.id; });
          if (idx < 0) {
            favs.push(p);
            saveFavorites(favs);
            if (typeof updateAllFavButtons === 'function') updateAllFavButtons();
            if (typeof updateFavBadge === 'function') updateFavBadge();
          }
        }
      }

      if (window.pendingOpenOrdersAfterLogin) {
        window.pendingOpenOrdersAfterLogin = false;
        openMyOrdersModal();
      } else if (window.pendingOpenFavAfterLogin) {
        window.pendingOpenFavAfterLogin = false;
        openFavModal();
      }
    }

    function useMyLocation() {
      if (!navigator.geolocation) {
        alert('⚠️ Seu navegador não suporta geolocalização. Por favor, digite seu CEP.');
        openCepModal();
        return;
      }

      navigator.geolocation.getCurrentPosition(
        async function(position) {
          var lat = position.coords.latitude;
          var lon = position.coords.longitude;

          try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`);
            const data = await res.json();
            const addr = data.address || {};

            var rawCep = (addr.postcode || '').replace(/\D/g, '');
            var city = addr.city || addr.town || addr.municipality || addr.village || '';
            var state = addr.state || '';

            if (state.length > 2) {
              var stateMap = {
                'Santa Catarina': 'SC', 'São Paulo': 'SP', 'Rio de Janeiro': 'RJ', 'Minas Gerais': 'MG',
                'Paraná': 'PR', 'Rio Grande do Sul': 'RS', 'Bahia': 'BA', 'Goiás': 'GO', 'Pernambuco': 'PE',
                'Ceará': 'CE', 'Pará': 'PA', 'Maranhão': 'MA', 'Espírito Santo': 'ES', 'Paraíba': 'PB',
                'Mato Grosso': 'MT', 'Mato Grosso do Sul': 'MS', 'Distrito Federal': 'DF', 'Rio Grande do Norte': 'RN',
                'Piauí': 'PI', 'Alagoas': 'AL', 'Sergipe': 'SE', 'Rondônia': 'RO', 'Tocantins': 'TO',
                'Acre': 'AC', 'Amapá': 'AP', 'Roraima': 'RR', 'Amazonas': 'AM'
              };
              state = stateMap[state] || state;
            }

            if (rawCep.length === 8) {
              var formattedCep = rawCep.substring(0, 5) + '-' + rawCep.substring(5, 8);
              var pdpInput = document.getElementById('pdpCepInput');
              if (pdpInput) pdpInput.value = formattedCep;
              var modalInput = document.getElementById('modalCepInput');
              if (modalInput) modalInput.value = formattedCep;
            }

            if (city) {
              var cityStateStr = `${city}/${state}`;
              try {
                localStorage.setItem('userCep', formattedCep);
                localStorage.setItem('userCityState', cityStateStr);
              } catch(e) {}
              if (typeof checkUserLocation === 'function') checkUserLocation();
            } else {
              openCepModal();
            }
          } catch(err) {
            openCepModal();
          }
        },
        function(error) {
          var msg = 'Permissão de localização não concedida. Digite seu CEP no campo.';
          if (error.code === error.PERMISSION_DENIED) {
            msg = 'Permissão de localização negada pelo navegador. Por favor, digite o seu CEP no campo acima.';
          }
          alert('⚠️ ' + msg);
          openCepModal();
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    }

    /* REDIRECIONAMENTO DIRETO PARA O MEU CARRINHO / CHECKOUT */
    function handleBuy(type) {
      var prodId = "<?= $product['id'] ?>";
      window.location.href = "carrinho.php?id=" + prodId;
    }

    /* ALTERNAR EXPANSÃO DO ACORDEÃO */
    function toggleAccordion(header) {
      var content = header.nextElementSibling;
      var icon = header.querySelector('.accordion-icon');
      
      if (!content) return;
      
      if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        if (icon) {
          icon.innerHTML = '<polyline points="18 15 12 9 6 15"/>';
        }
      } else {
        content.style.display = 'none';
        if (icon) {
          icon.innerHTML = '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>';
        }
      }
    }

    function handleStickyScroll() {
      var bar = document.getElementById('stickyBottomBar');
      if (!bar) return;
      var scrollPos = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
      if (scrollPos > 40) {
        bar.classList.add('visible');
        bar.style.transform = 'translateY(0)';
      } else {
        bar.classList.remove('visible');
        bar.style.transform = 'translateY(100%)';
      }
    }

    window.addEventListener('scroll', handleStickyScroll, { passive: true });
    window.addEventListener('touchmove', handleStickyScroll, { passive: true });
    document.addEventListener('DOMContentLoaded', handleStickyScroll);
  </script>
</head>
<body>

  <!-- Top Announcement Bar & Expandable Drawer (100% IDÊNTICO À CAPTURA MEDIA_1787175668684.JPG) -->
  <div class="top-announcement-wrapper" id="topAnnouncementWrapper">
    <div class="top-announcement" id="topAnnouncementToggle" onclick="toggleNewsletter()">
      <span>Receba em primeira mão <strong>ofertas exclusivas</strong></span>
      <svg id="announcementArrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" style="transition: transform 0.3s ease;"><polyline points="18 15 12 9 6 15"/></svg>
    </div>

    <!-- Collapsible Newsletter Form -->
    <div class="newsletter-drawer" id="newsletterDrawer" style="display: none; padding: 12px 16px 20px 16px; background-color: #002eb3;">
      <form id="newsletterForm" onsubmit="handleNewsletterSubmit(event)">
        <div class="form-group-top" style="margin-bottom: 10px;">
          <input type="email" id="newsEmail" placeholder="Digite seu e-mail" style="width: 100%; height: 46px; padding: 0 14px; border-radius: 6px; border: none; font-size: 15px; outline: none; background: #ffffff; color: #333;" required>
        </div>
        <div class="form-group-top" style="margin-bottom: 12px;">
          <input type="tel" id="newsPhone" placeholder="Digite seu telefone" style="width: 100%; height: 46px; padding: 0 14px; border-radius: 6px; border: none; font-size: 15px; outline: none; background: #ffffff; color: #333;" required>
        </div>
        <button type="submit" class="btn-news-submit" style="width: 100%; height: 48px; background-color: #168019; color: #ffffff; font-size: 16px; font-weight: 800; border: none; border-radius: 6px; cursor: pointer; margin-bottom: 12px;">Receber</button>
        
        <p class="news-terms" style="font-size: 12px; color: #ffffff; text-align: center; line-height: 1.45; opacity: 0.95;">
          Ao enviar seus dados, você aceita os <a href="#" style="color: #ffffff; text-decoration: underline;">Termos de Uso</a> e a <a href="#" style="color: #ffffff; text-decoration: underline;">Política de Privacidade</a> e autoriza receber ofertas. Cancele quando quiser.
        </p>
      </form>

      <div id="newsletterSuccess" class="newsletter-success-msg" style="display: none; color: #ffffff; background: rgba(255,255,255,0.15); padding: 14px; border-radius: 6px; text-align: center; font-size: 14px; font-weight: 700; margin-top: 6px;">
        ✓ Sucesso! As ofertas exclusivas serão enviadas em primeira mão para o seu e-mail e telefone!
      </div>
    </div>
  </div>

  <script>
    function startFlashTimer() {
      // Contador entre 50h e 70h aleatório
      var totalSeconds = (Math.floor(Math.random() * 20) + 50) * 3600
                       + Math.floor(Math.random() * 59) * 60
                       + Math.floor(Math.random() * 59);

      function tick() {
        if (totalSeconds <= 0) return;
        totalSeconds--;
        var h = Math.floor(totalSeconds / 3600);
        var m = Math.floor((totalSeconds % 3600) / 60);
        var s = totalSeconds % 60;
        var eH = document.getElementById('flashTimerHours');
        var eM = document.getElementById('flashTimerMin');
        var eS = document.getElementById('flashTimerSec');
        if (eH) eH.textContent = String(h).padStart(2,'0') + 'H';
        if (eM) eM.textContent = String(m).padStart(2,'0') + 'M';
        if (eS) eS.textContent = String(s).padStart(2,'0') + 'S';
      }
      tick();
      setInterval(tick, 1000);
    }
  </script>



  <!-- Top Red Divider Line -->
  <div class="top-red-line"></div>

  <!-- Main Sticky Header -->
  <header class="main-header">
    <div class="header-top-row">
      <div class="header-left-group">
        <button class="header-icon-btn" onclick="openNavDrawer()" aria-label="Menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
        
        <a href="home.php" class="brand-logo">
          CASAS<span>BAHIA</span>
        </a>
      </div>

      <div class="header-actions">
        <button class="header-icon-btn" aria-label="Perfil" onclick="handleUserHeaderClick()">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
        <div class="fav-header-wrap">
          <button class="header-icon-btn" aria-label="Favoritos" onclick="openFavModal()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <span class="fav-count-badge" id="favBadge"></span>
        </div>
        <button class="header-icon-btn" aria-label="Carrinho" onclick="window.location.href='carrinho.php?empty=1'">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </button>
      </div>
    </div>

    <!-- Search Input Box -->
    <div class="search-box-wrapper">
      <div class="search-input-container">
        <input type="text" placeholder="O que você tá procurando?" aria-label="Pesquisar">
        <span class="search-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </span>
      </div>
    </div>

    <!-- CEP Locator Bar -->
    <div class="cep-delivery-bar" id="cepBarToggle" onclick="openCepModal()">
      <div class="cep-left-info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        <div class="cep-text-container" id="cepTextContainer">
          <span class="cep-label-only">Informe seu CEP</span>
        </div>
      </div>

      <!-- Ícone de Acessibilidade Oficial -->
      <div class="cep-accessibility-icon" title="Acessibilidade">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/>
          <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
          <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>
  </header>

  <?php require_once __DIR__ . '/includes/search_modal.php'; ?>
  <?php require_once __DIR__ . '/includes/account_modal.php'; ?>

  <!-- NAVIGATION SIDE DRAWER -->
  <div class="nav-drawer-overlay" id="navDrawerOverlay" onclick="closeNavDrawer(event)">
    <div class="nav-drawer-content" onclick="event.stopPropagation()">
      <div class="nav-drawer-header">
        <button class="nav-drawer-close-btn" onclick="closeNavDrawer(event)" aria-label="Fechar Menu">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="nav-drawer-user-box">
        <div class="nav-user-info-row">
          <div class="nav-user-avatar" title="Casas Bahia">
            <svg width="34" height="34" viewBox="0 0 36 36" fill="none">
              <rect width="36" height="36" rx="8" fill="#ffffff"/>
              <path d="M7 13L13 7L19 13" fill="#e30613"/>
              <path d="M12 11H19C21.2 11 23 12.3 23 14.5C23 16 22 17.2 20.5 17.7C22.2 18.3 23.5 19.8 23.5 21.8C23.5 24.1 21.5 25.5 19 25.5H12V11ZM15.5 14V16.8H18.8C19.8 16.8 20.5 16.2 20.5 15.4C20.5 14.6 19.8 14 18.8 14H15.5ZM15.5 19.2V22.5H19.2C20.3 22.5 21 21.8 21 20.85C21 19.9 20.3 19.2 19.2 19.2H15.5Z" fill="#003399"/>
            </svg>
          </div>
          <div>
            <div class="nav-user-title" id="navUserTitle">Olá, visitante</div>
            <div class="nav-user-subtitle" id="navUserSubtitle">Acesse sua conta ou cadastre-se para ver seus pedidos</div>
          </div>
        </div>
        <button class="btn-nav-login" id="btnNavLogin" onclick="handleNavLoginClick()">Entre ou Cadastre-se</button>
      </div>

      <div class="nav-quick-cards-row">
        <div class="nav-quick-card" onclick="openMyOrdersModal()" style="cursor: pointer;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          <span>Meus<br>Pedidos</span>
        </div>
        <div class="nav-quick-card" onclick="openFavModal()" style="cursor: pointer;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          <span>Meus<br>Favoritos</span>
        </div>
        <div class="nav-quick-card" onclick="window.location.href='carrinho.php?empty=1'" style="cursor: pointer;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <span>Meu<br>Carrinho</span>
        </div>
      </div>

      <div class="nav-departments-section">
        <h3 class="nav-departments-title">Compre por departamentos</h3>
        
        <ul class="nav-departments-list">
          <li>
            <a href="#">
              <div class="dept-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.8"><path d="M2 17h20v2H2zM4 17V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v9"/></svg>
                <span>Móveis</span>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </li>
          <li>
            <a href="#">
              <div class="dept-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.8"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                <span>Celulares e Smartphones</span>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </li>
          <li>
            <a href="#">
              <div class="dept-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.8"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="10" y1="6" x2="10" y2="8"/><line x1="10" y1="14" x2="10" y2="18"/></svg>
                <span>Eletrodomésticos</span>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </li>
          <li>
            <a href="#">
              <div class="dept-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.8"><path d="M6 3h12v4H6zM8 7v14M16 7v14"/></svg>
                <span>Eletroportáteis</span>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </li>
          <li>
            <a href="#">
              <div class="dept-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>TVs e Vídeos</span>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- OVERLAY / MODAL FULL-SCREEN: IDENTIFICAÇÃO -->
  <div class="login-overlay" id="loginModalOverlay" style="display: none;">
    <div class="login-content">
      <div class="top-red-line"></div>

      <div class="login-header-row">
        <a href="home.php" class="brand-logo">
          CASAS<span>BAHIA</span>
        </a>
        <div class="cep-accessibility-icon" title="Acessibilidade">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8">
            <circle cx="12" cy="12" r="10"/>
            <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
            <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <div class="login-body">
        <button class="btn-login-back" onclick="closeLoginModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#444444" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Voltar
        </button>

        <h2 class="login-main-title">Identificação</h2>

        <h3 class="login-subtitle-heading">Entre ou cadastre-se</h3>
        <p class="login-subtext-desc">Para começar, digite seu CPF ou CNPJ no campo abaixo.</p>

        <form id="loginIdentForm" onsubmit="handleLoginSubmit(event)">
          <div class="form-group-login">
            <label for="loginCpfInput">CPF ou CNPJ</label>
            <input type="text" id="loginCpfInput" placeholder="" maxlength="18" oninput="formatCpfCnpj(this)">
            <span class="login-example-text">Exemplo: 123.456.789-00 ou 12.345.678/0001-95</span>
          </div>

          <!-- CAMPO DE SENHA (APARECE APÓS DIGITAR O CPF OU CLICAR EM CONTINUAR) -->
          <div class="form-group-login" id="loginPasswordGroup" style="display: none; margin-top: -12px; margin-bottom: 24px;">
            <label for="loginPasswordInput">Senha</label>
            <input type="password" id="loginPasswordInput" placeholder="Digite sua senha" oninput="checkLoginPassword(this)">
            <a href="#" style="font-size: 13px; color: #003399; text-decoration: underline; display: inline-block; margin-top: 6px; font-weight: 600;">Esqueci minha senha</a>
          </div>

          <button type="submit" class="btn-login-continue" id="btnLoginContinue" disabled>Continuar</button>
        </form>

        <div class="social-login-divider">
          <span>Entre com suas redes sociais</span>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: Escolha sua Localização -->
  <div class="modal-overlay" id="cepModalOverlay" style="display: none;" onclick="closeCepModal(event)">
    <div class="modal-card" onclick="event.stopPropagation()">
      <div class="modal-header-blue">
        <span>Escolha sua localização</span>
        <button class="modal-close-btn" id="cepModalClose" onclick="closeCepModal(event)" aria-label="Fechar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="modal-body">
        <p class="modal-subtext">A gente te ajuda a encontrar o seu produto com menor frete e prazo de entrega.</p>

        <form id="modalCepForm">
          <div style="margin-bottom: 16px;">
            <label for="modalCepInput" class="modal-input-label">Digite seu CEP</label>
            <input type="text" id="modalCepInput" placeholder="_____-___" maxlength="9" required>
          </div>
          <button type="submit" class="btn-modal-confirm" id="btnModalConfirm">Confirmar</button>
          <div id="modalCepError" class="modal-error-text" style="display: none;"></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Page Content Container -->
  <main class="container">
    
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
      casasbahia.com.br <span>›</span> TV e Vídeo <span>›</span> Televisores <span>›</span> Smart TV
    </div>

    <!-- Badges Row (Selo Amarelo Rotativo + Selo Vermelho Piscando) -->
    <div class="product-badges-row">
      <?php 
        $b1 = trim($product['badge_1'] ?? 'BRASIL');
        $b2 = trim($product['badge_2'] ?? 'LANÇAMENTO★');
        $isCustomB1 = (!empty($b1) && strcasecmp($b1, 'BRASIL') !== 0);
      ?>
      <?php if (!empty($b1)): ?>
        <span class="badge badge-yellow" id="badgeYellowRotating" data-custom="<?= $isCustomB1 ? 'true' : 'false' ?>"><?= htmlspecialchars($b1) ?></span>
      <?php endif; ?>

      <?php if (!empty($b2)): ?>
        <span class="badge badge-red badge-blinking"><?= htmlspecialchars($b2) ?></span>
      <?php endif; ?>
    </div>

    <!-- Product Title -->
    <h1 class="product-title"><?= htmlspecialchars($product['title']) ?></h1>

    <!-- Meta Info -->
    <div class="product-meta">
      <span class="item-code">(Cód. Item <?= htmlspecialchars($product['item_code']) ?>)</span>
      <span class="meta-divider">|</span>
      <span>Outros produtos <a href="#" class="brand-link"><?= htmlspecialchars($product['brand']) ?></a></span>
    </div>

    <!-- Image Gallery Section -->
    <div class="gallery-wrapper" id="gallerySection">
      <!-- Discount Overlay Tag -->
      <div class="discount-tag-overlay">
        <span class="disc-percent"><?= htmlspecialchars($product['discount_percentage']) ?>%</span>
        <span class="disc-text">OFF</span>
      </div>

      <!-- Gallery Overlay Action Buttons -->
      <div class="gallery-action-btns">
        <button class="circle-btn" id="fav-pdp-<?= $product['id'] ?>" aria-label="Favoritar"
          onclick="toggleFavoritePDP(<?= $product['id'] ?>, <?= htmlspecialchars(json_encode($product['title'])) ?>, <?= floatval($product['pix_price'] ?? $product['regular_price'] ?? 0) ?>, <?= htmlspecialchars(json_encode($mainImage)) ?>)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
        <button class="circle-btn" aria-label="Compartilhar" onclick="handleProductShare(event)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        </button>
      </div>

      <!-- Main Image View -->
      <div class="main-image-container" id="mainImageWrapper">
        <?php 
          $mainExt = strtolower(pathinfo(parse_url($mainImage, PHP_URL_PATH), PATHINFO_EXTENSION));
        ?>
        <?php if (in_array($mainExt, ['mp4', 'webm', 'mov'])): ?>
          <video id="mainProductVideo" src="<?= htmlspecialchars($mainImage) ?>" autoplay loop muted playsinline style="max-width: 100%; max-height: 100%; object-fit: contain;"></video>
          <img id="mainProductImage" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>" style="display: none;">
        <?php else: ?>
          <img id="mainProductImage" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
        <?php endif; ?>
      </div>

      <!-- Thumbnails Carousel -->
      <div class="thumbnails-row">
        <!-- Play Video Icon Button (SVG Original) -->
        <div class="video-thumb-btn" title="Ver Vídeo">
          <svg width="44" height="44" viewBox="0 0 44 44" fill="none">
            <circle cx="22" cy="22" r="22" fill="#003399"/>
            <polygon points="17,14 31,22 17,30" fill="#ffffff"/>
          </svg>
        </div>

        <?php foreach ($images as $index => $img): ?>
          <?php 
            $tExt = strtolower(pathinfo(parse_url($img['image_url'], PHP_URL_PATH), PATHINFO_EXTENSION));
          ?>
          <div class="thumb-item <?= $index === 0 ? 'active' : '' ?>" data-src="<?= htmlspecialchars($img['image_url']) ?>">
            <?php if (in_array($tExt, ['mp4', 'webm', 'mov'])): ?>
              <video src="<?= htmlspecialchars($img['image_url']) ?>" autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
            <?php else: ?>
              <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Thumbnail <?= $index + 1 ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Product Manual Link -->
    <a href="#" class="manual-link">Manual do produto</a>

    <!-- Specifications Briefing (Com Abertura do Modal ao Clicar em Ver mais) -->
    <section class="spec-box">
      <div class="spec-header">
        <span>Características do produto</span>
        <a href="#" onclick="openSpecsModal(event)" class="spec-more-link">
          Ver mais 
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
      <?php if (!empty($specs)): ?>
        <?php foreach (array_slice($specs, 0, 4) as $spec): ?>
          <div class="spec-item">
            <strong><?= htmlspecialchars($spec['spec_name']) ?>:</strong> <?= htmlspecialchars($spec['spec_value']) ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="spec-item"><strong>Marca:</strong> <?= htmlspecialchars($product['brand']) ?></div>
        <div class="spec-item"><strong>Código do Item:</strong> <?= htmlspecialchars($product['item_code']) ?></div>
        <div class="spec-item"><strong>Garantia do Fabricante:</strong> 12 Meses</div>
        <div class="spec-item"><strong>Voltagem:</strong> Bivolt (110V / 220V)</div>
      <?php endif; ?>
    </section>

    <!-- Reviews Summary Row (Pill estilizada idêntica à Casas Bahia - 100% em uma linha só) -->
    <div class="reviews-summary-row" style="cursor: pointer; background: #f4f6fb; border-radius: 8px; padding: 10px 12px; border: none; display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 14px; font-size: 13px; white-space: nowrap; flex-wrap: nowrap;" onclick="scrollToReviews(event)">
      <div class="star-rating" style="display: flex; align-items: center; gap: 4px; color: #333333; font-weight: 700; flex-shrink: 0;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="#d97706" stroke="none">
          <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
        </svg>
        <span><?= number_format($product['rating'], 1) ?></span>
      </div>

      <div style="width: 1px; height: 14px; background: #cbd5e1; flex-shrink: 0;"></div>

      <div class="review-meta-item" style="display: flex; align-items: center; gap: 5px; color: #003399; font-weight: 700; flex-shrink: 0;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span><?= htmlspecialchars($product['reviews_count']) ?> avaliações</span>
      </div>

      <div style="width: 1px; height: 14px; background: #cbd5e1; flex-shrink: 0;"></div>

      <div class="review-meta-item" style="display: flex; align-items: center; gap: 5px; color: #003399; font-weight: 700; flex-shrink: 0;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span><?= htmlspecialchars($product['questions_count']) ?> perguntas</span>
      </div>
    </div>

    <!-- SELEÇÃO DE VARIAÇÕES (VOLTAGEM / TAMANHO) IDÊNTICO À CASAS BAHIA (MEDIA_1787234599412.JPG) -->
    <?php if (!empty($product['variation_name']) && !empty($product['variation_options'])): ?>
      <?php 
        $varOptions = array_filter(array_map('trim', explode(',', $product['variation_options'])));
        $defaultOpt = !empty($product['variation_default']) ? trim($product['variation_default']) : ($varOptions[0] ?? '');
      ?>
      <?php if (!empty($varOptions)): ?>
        <div class="variation-selection-card">
          <div class="variation-label-row">
            <span class="var-title"><?= htmlspecialchars($product['variation_name']) ?>:</span> 
            <strong id="selectedVariationText" class="var-selected-val"><?= htmlspecialchars($defaultOpt) ?></strong>
          </div>
          <div class="variation-buttons-grid">
            <?php foreach ($varOptions as $idx => $opt): ?>
              <?php $isSelected = ($opt === $defaultOpt); ?>
              <button type="button" 
                      class="variation-btn <?= $isSelected ? 'active' : '' ?>" 
                      onclick="selectProductVariation(this, '<?= htmlspecialchars(addslashes($opt)) ?>')">
                <?= htmlspecialchars($opt) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <section class="pricing-container" style="padding: 0; margin: 0;">

      <!-- BARRA OFERTAS RELÂMPAGO IDÊNTICA À FOTO - UMA SÓ LINHA (SEMPRE COMPACTO) -->
      <div style="display:flex; align-items:center; justify-content:space-between; background:#f4f6fb; border-radius:10px; padding:8px 10px; margin-bottom:14px; border:1px solid #e2e8f3; white-space:nowrap; flex-wrap:nowrap;">
        <div style="display:flex; align-items:center; gap:5px; flex-shrink:0;">
          <span style="font-size:14px; font-weight:700; color:#003399;">Ofertas Relâmpago</span>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="#d9381e"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>

        <!-- Capsule azul contendo relógio + caixas brancas -->
        <div style="background:#003399; border-radius:8px; padding:3px 5px; display:inline-flex; align-items:center; gap:4px; flex-shrink:0;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="margin-left:1px; margin-right:1px; flex-shrink:0;">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
          <div id="priceTimerH" style="background:#ffffff; color:#003399; border-radius:5px; padding:3px 6px; font-size:11px; font-weight:700; text-align:center;">60 H</div>
          <div id="priceTimerM" style="background:#ffffff; color:#003399; border-radius:5px; padding:3px 6px; font-size:11px; font-weight:700; text-align:center;">53 M</div>
          <div id="priceTimerS" style="background:#ffffff; color:#003399; border-radius:5px; padding:3px 6px; font-size:11px; font-weight:700; text-align:center;">57 s</div>
        </div>
      </div>

      <!-- PREÇO RISCADO + BADGE BAIXOU -->
      <?php $discPct = intval($product['pix_discount_percentage'] ?? 0); ?>
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
        <span style="font-size:14px; color:#999; text-decoration:line-through; font-weight:400;">R$ <?= number_format($product['regular_price'], 2, ',', '.') ?></span>
        <?php if ($discPct > 0): ?>
        <span style="background:#168019; color:#fff; font-size:13px; font-weight:700; padding:3px 10px; border-radius:6px;">Baixou <?= $discPct ?>%</span>
        <?php endif; ?>
      </div>

      <!-- PREÇO PIX GRANDE -->
      <div class="main-pix-price" id="mainPixPrice" data-base-price="<?= htmlspecialchars($product['pix_price']) ?>" style="font-size:36px; font-weight:700; color:#003399; letter-spacing:-0.5px; line-height:1.1; margin-bottom:4px;">
        R$ <?= number_format($product['pix_price'], 2, ',', '.') ?>
      </div>

      <!-- PARCELAS — "ou em até Nx de R$ X sem juros" -->
      <div style="font-size:14px; color:#444; font-weight:400; margin-bottom:18px;">
        ou em até <strong style="font-weight:600;"><?= htmlspecialchars($product['max_installments']) ?>x de R$ <?= number_format($product['installment_value'], 2, ',', '.') ?></strong> sem juros
      </div>

      <!-- DIVISÓRIA -->
      <div style="height:1px; background:#e5eaf3; margin-bottom:14px;"></div>

      <!-- CARNÊ DIGITAL -->
      <div style="display:flex; align-items:center; gap:12px; padding-bottom:14px;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="1.5" style="flex-shrink:0;">
          <rect x="3" y="4" width="18" height="16" rx="2"/>
          <line x1="3" y1="9" x2="21" y2="9"/>
          <line x1="8" y1="4" x2="8" y2="9"/>
          <line x1="16" y1="4" x2="16" y2="9"/>
        </svg>
        <div style="flex:1; font-size:13px; color:#555; font-weight:400; line-height:1.5;">
          Parcele suas compras em até <?= htmlspecialchars($product['carne_max_installments']) ?>x no <strong style="font-weight:600;">Carnê Digital</strong>.
        </div>
        <a href="#" onclick="return false;" style="color:#003399; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; flex-shrink:0;">Comprar agora</a>
      </div>

      <!-- DIVISÓRIA -->
      <div style="height:1px; background:#e5eaf3; margin-bottom:14px;"></div>

      <!-- VER MAIS OPÇÕES DE PAGAMENTO -->
      <a href="#" onclick="return false;" style="display:flex; align-items:center; gap:4px; color:#003399; font-size:15px; font-weight:700; text-decoration:none; margin-bottom:4px;">
        Ver mais opções de pagamento
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </a>


    </section>

    <script>
    (function(){
      var t=(Math.floor(Math.random()*20)+50)*3600+Math.floor(Math.random()*59)*60+Math.floor(Math.random()*59);
      function tick(){
        if(t<=0)return; t--;
        var h=Math.floor(t/3600),m=Math.floor((t%3600)/60),s=t%60;
        var eH=document.getElementById('priceTimerH'),eM=document.getElementById('priceTimerM'),eS=document.getElementById('priceTimerS');
        if(eH)eH.textContent=String(h).padStart(2,'0')+' H';
        if(eM)eM.textContent=String(m).padStart(2,'0')+' M';
        if(eS)eS.textContent=String(s).padStart(2,'0')+' s';
      }
      tick(); setInterval(tick,1000);
    })();
    </script>

    <!-- Primary Action Buttons -->
    <div class="action-buttons-container">
      <button class="btn-buy-primary" id="buyButtonPrimary" onclick="handleBuy('comprar')">Comprar</button>
      <button class="btn-pickup-secondary" onclick="handleBuy('retira_rapido')">Retira Rápido</button>
    </div>

    <!-- Vendido e entregue por Casas Bahia -->
    <div class="seller-info-row">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      <span>Vendido e entregue por <a href="#" class="seller-link">Casas Bahia</a></span>
    </div>

    <!-- Calcular o frete e prazo de entrega Card Box -->
    <div class="shipping-calc-card">
      <h3 class="shipping-card-title">Calcular o frete e prazo de entrega</h3>
      <form id="pdpShippingForm" onsubmit="handlePdpCepSubmit(event)">
        <input type="text" id="pdpCepInput" placeholder="Digite seu CEP" maxlength="9" class="shipping-input" oninput="handlePdpCepInput(this)" value="">
        <button type="button" class="btn-use-location" onclick="useMyLocation()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
          Usar minha localização
        </button>
      </form>
      
      <!-- Resultado da Consulta de Frete -->
      <div id="pdpShippingResult" style="display: none; margin-top: 14px; border-top: 1px solid #dbe4ff; padding-top: 14px;">
        <div style="font-size: 14px; font-weight: 600; color: #003399; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
          <span id="pdpShippingCity">Sua região</span>
        </div>

        <div style="font-size: 14px; font-weight: 700; color: #003399; margin-bottom: 8px;">Receba em casa</div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: #444; border-bottom: 1px solid #e5e5e5; padding-bottom: 10px; margin-bottom: 12px;">
          <div>
            <div style="font-size: 13px; color: #777;">Normal</div>
            <div style="font-weight: 600; color: #333;" id="pdpShippingDeadline">até 11 de setembro, sexta</div>
          </div>
          <div style="font-weight: 800; color: #168019; font-size: 15px;">Grátis</div>
        </div>

        <div style="text-align: center;">
          <a href="#" style="font-size: 13px; font-weight: 700; color: #003399; text-decoration: underline;">Ver regras de frete</a>
        </div>
      </div>
    </div>


    <!-- Itens que combinam com seu produto -->
    <?php if (!empty($combinamProducts)): ?>
    <section class="sponsored-section">
      <h2 class="sponsored-title">Itens que combinam com seu produto</h2>
      <div class="sponsored-carousel">
        <?php foreach ($combinamProducts as $spon):
          $pixPrice   = !empty($spon['pix_price'])     ? (float)$spon['pix_price']      : (float)$spon['price'];
          $regPrice   = !empty($spon['regular_price'])  ? (float)$spon['regular_price']  : 0;
          $discount   = !empty($spon['discount_percentage']) ? (int)$spon['discount_percentage'] : 0;
          $rating     = !empty($spon['rating'])         ? (float)$spon['rating']         : 4.0;
          $reviewsCnt = !empty($spon['reviews_count'])  ? (int)$spon['reviews_count']    : 0;
          $hasFrete   = !empty($spon['free_shipping'])  || ($pixPrice < 300);
          $imgSrc     = !empty($spon['main_image'])     ? htmlspecialchars($spon['main_image']) : 'assets/images/product_aoc_main.png';
        ?>
          <a href="index.php?id=<?= $spon['id'] ?>" class="sponsored-card">
            <div class="sponsored-card-img-wrap">
              <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($spon['title']) ?>" loading="lazy">
              <div class="sponsored-card-badge">COMPRE E<br>RETIRE EM</div>
            </div>
            <div class="sponsored-card-body">
              <div class="sponsored-card-title"><?= htmlspecialchars($spon['title']) ?></div>
              <div class="sponsored-card-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <svg viewBox="0 0 24 24" fill="<?= $s <= round($rating) ? '#e67e22' : '#ddd' ?>"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <?php endfor; ?>
                <?php if ($reviewsCnt > 0): ?><span>(<?= $reviewsCnt ?>)</span><?php endif; ?>
              </div>
              <?php if ($regPrice > 0 && $regPrice > $pixPrice): ?>
                <div class="sponsored-card-old-price">
                  R$ <?= number_format($regPrice, 2, ',', '.') ?>
                  <?php if ($discount > 0): ?><span class="sponsored-card-discount">-<?= $discount ?>%</span><?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="sponsored-card-price">R$ <?= number_format($pixPrice, 2, ',', '.') ?></div>
              <?php if ($pixPrice > 0): ?><div class="sponsored-card-pix">No Pix</div><?php endif; ?>
            </div>
            <?php if ($hasFrete): ?><div class="sponsored-card-frete">FRETE GRÁTIS</div><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- SEÇÃO ACORDEÃO -->
    <section class="pdp-accordion-section">
      <!-- 1. Descrição do produto -->
      <div class="accordion-item">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <span>Descrição do produto</span>
          <svg class="accordion-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="accordion-content" style="display: none;">
          <?php
            $rawDesc = !empty($product['description']) ? $product['description'] : 'Excelente produto de alta qualidade, tecnologia de ponta e design moderno para o seu dia a dia. Conta com garantia do fabricante e suporte completo.';
            $cleanDesc = html_entity_decode($rawDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanDesc = preg_replace('/<br\s*\/?>/i', "\n", $cleanDesc);
            $cleanDesc = preg_replace('/<\/p>/i', "\n\n", $cleanDesc);
            $cleanDesc = strip_tags($cleanDesc);
            $cleanDesc = preg_replace("/\n{3,}/", "\n\n", trim($cleanDesc));
          ?>
          <p style="white-space: pre-line; line-height: 1.6; font-size: 14px; color: #444;"><?= htmlspecialchars($cleanDesc) ?></p>
        </div>
      </div>

      <!-- 2. Características -->
      <div class="accordion-item">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <span>Características</span>
          <svg class="accordion-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        <div class="accordion-content" style="display: none;">
          <ul class="accordion-bullets-list">
            <?php if (!empty($specs)): ?>
              <?php foreach ($specs as $sp): ?>
                <li><strong><?= htmlspecialchars($sp['spec_name']) ?>:</strong> <?= htmlspecialchars($sp['spec_value']) ?></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>Produto original com garantia da fabricante <strong><?= htmlspecialchars($product['brand']) ?></strong></li>
              <li>Código do Item: <strong><?= htmlspecialchars($product['item_code']) ?></strong></li>
              <li>Design moderno, elegante e de alto desempenho</li>
              <li>Excelente custo-benefício e facilidade de manuseio</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- 3. Especificações Técnicas -->
      <div class="accordion-item">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <span>Especificações Técnicas</span>
          <svg class="accordion-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        <div class="accordion-content" style="display: none;">
          <table class="accordion-specs-table">
            <tr><td><strong>Marca:</strong></td><td><?= htmlspecialchars($product['brand']) ?></td></tr>
            <tr><td><strong>Código do Item:</strong></td><td><?= htmlspecialchars($product['item_code']) ?></td></tr>
            <tr><td><strong>Modelo / SKU:</strong></td><td><?= htmlspecialchars($product['sku']) ?></td></tr>
            <?php if (!empty($specs)): ?>
              <?php foreach ($specs as $sp): ?>
                <tr><td><strong><?= htmlspecialchars($sp['spec_name']) ?>:</strong></td><td><?= htmlspecialchars($sp['spec_value']) ?></td></tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td><strong>Garantia do Fabricante:</strong></td><td>12 Meses</td></tr>
              <tr><td><strong>Voltagem:</strong></td><td>Bivolt (110V / 220V)</td></tr>
            <?php endif; ?>
          </table>
        </div>
      </div>

      <!-- 4. Dimensões -->
      <div class="accordion-item">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <span>Dimensões</span>
          <svg class="accordion-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        <div class="accordion-content" style="display: none;">
          <table class="accordion-specs-table">
            <tr><td><strong>Produto:</strong></td><td><?= htmlspecialchars($product['title']) ?></td></tr>
            <tr><td><strong>Garantia do Fabricante:</strong></td><td>12 Meses</td></tr>
            <tr><td><strong>Fabricante:</strong></td><td><?= htmlspecialchars($product['brand']) ?></td></tr>
          </table>
        </div>
      </div>
    </section>

    <!-- Acreditamos que você pode gostar -->
    <?php if (!empty($gostarProducts)): ?>
    <section class="sponsored-section">
      <h2 class="sponsored-title">Acreditamos que você pode gostar</h2>
      <div class="sponsored-carousel">
        <?php foreach ($gostarProducts as $spon):
          $pixPrice   = !empty($spon['pix_price'])          ? (float)$spon['pix_price']          : (float)$spon['price'];
          $regPrice   = !empty($spon['regular_price'])       ? (float)$spon['regular_price']       : 0;
          $discount   = !empty($spon['discount_percentage']) ? (int)$spon['discount_percentage']   : 0;
          $rating     = !empty($spon['rating'])              ? (float)$spon['rating']              : 4.0;
          $reviewsCnt = !empty($spon['reviews_count'])       ? (int)$spon['reviews_count']         : 0;
          $hasFrete   = !empty($spon['free_shipping'])  || ($pixPrice < 300);
          $imgSrc     = !empty($spon['main_image'])          ? htmlspecialchars($spon['main_image']) : 'assets/images/product_aoc_main.png';
        ?>
          <a href="index.php?id=<?= $spon['id'] ?>" class="sponsored-card">
            <div class="sponsored-card-img-wrap">
              <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($spon['title']) ?>" loading="lazy">
            </div>
            <div class="sponsored-card-body">
              <div class="sponsored-card-title"><?= htmlspecialchars($spon['title']) ?></div>
              <div class="sponsored-card-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <svg viewBox="0 0 24 24" fill="<?= $s <= round($rating) ? '#e67e22' : '#ddd' ?>"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <?php endfor; ?>
                <?php if ($reviewsCnt > 0): ?><span>(<?= $reviewsCnt ?>)</span><?php endif; ?>
              </div>
              <?php if ($regPrice > 0 && $regPrice > $pixPrice): ?>
                <div class="sponsored-card-old-price">
                  R$ <?= number_format($regPrice, 2, ',', '.') ?>
                  <?php if ($discount > 0): ?><span class="sponsored-card-discount">-<?= $discount ?>%</span><?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="sponsored-card-price">R$ <?= number_format($pixPrice, 2, ',', '.') ?></div>
              <?php if ($pixPrice > 0): ?><div class="sponsored-card-pix">No Pix</div><?php endif; ?>
            </div>
            <?php if ($hasFrete): ?><div class="sponsored-card-frete">FRETE GRÁTIS</div><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- SEÇÃO: AVALIAÇÕES DE CLIENTES E RESUMO DE IA -->
    <section class="customer-reviews-section" id="customerReviewsSection">
      <h2 class="reviews-section-title">Avaliações de Clientes</h2>

      <div class="reviews-rating-header">
        <div class="rating-number-row">
          <span class="rating-big-num"><?= number_format($product['rating'], 1) ?></span>
          <div class="rating-stars-list">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
          </div>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#333333" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="rating-count-sub">Total de <?= htmlspecialchars($product['reviews_count']) ?> avaliações</div>
      </div>

      <!-- Recomendação do produto -->
      <div class="recommendation-box">
        <h3 class="recommendation-title">Recomendação do produto</h3>
        
        <div class="recommend-card positive">
          <div class="recommend-percent">98%</div>
          <div class="recommend-text">dos clientes <strong>recomendam</strong> esse produto.</div>
        </div>

        <div class="recommend-card negative">
          <div class="recommend-percent">2%</div>
          <div class="recommend-text">dos clientes <strong>não recomendam</strong> esse produto.</div>
        </div>
      </div>

      <!-- Resumo das avaliações (IA Card Dinâmico) -->
      <div class="ai-summary-box">
        <h3 class="ai-summary-title">Resumo das avaliações</h3>

        <div class="ai-summary-card">
          <p class="ai-summary-paragraph">
            Os clientes destacam a qualidade do produto <strong><?= htmlspecialchars($product['brand']) ?></strong>, mencionando que a imagem e desempenho são ótimos, a configuração é fácil e o custo-benefício é considerado excelente. Muitos usuários apreciaram a beleza estética e design do produto, além de destacarem a entrega rápida e eficiente das Casas Bahia.
          </p>

          <div class="ai-chips-row">
            <span class="ai-chip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#168019" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Qualidade</span>
            <span class="ai-chip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#168019" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Aparência</span>
            <span class="ai-chip"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#168019" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Preço</span>
          </div>

          <div class="ai-footer-note">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="2"><path d="M15 4L18 7M7 12L4 15M20 10L14 4L3 15L9 21L20 10Z"/></svg>
            <span>Resumo gerado pela IA com base em avaliações de clientes.</span>
          </div>
        </div>
      </div>

      <!-- WIDGET: AVALIAÇÕES C/ FOTOS DA CASAS BAHIA (CARROSSEL HORIZONTAL) -->
      <?php 
        $allReviewPhotos = [];
        if (!empty($dbReviews)) {
            foreach ($dbReviews as $rItem) {
                if (!empty($rItem['images'])) {
                    foreach ($rItem['images'] as $imgItem) {
                        $allReviewPhotos[] = [
                            'url' => $imgItem['image_url'],
                            'author' => $rItem['author'],
                            'rating' => $rItem['rating'],
                            'date' => $rItem['review_date'],
                            'content' => $rItem['content']
                        ];
                    }
                }
            }
        }
      ?>

      <?php if (!empty($allReviewPhotos)): ?>
        <div class="photo-reviews-widget-box" style="margin-top: 24px; margin-bottom: 24px; background: #ffffff; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
          <div style="font-size: 16px; font-weight: 800; color: #003399; display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            <span>Avaliações c/ fotos</span>
          </div>

          <div class="photo-reviews-carousel">
            <?php foreach ($allReviewPhotos as $pPhoto): ?>
              <div class="photo-carousel-card" onclick="openReviewPhotoModal('<?= htmlspecialchars(addslashes($pPhoto['url'])) ?>', '<?= htmlspecialchars(addslashes($pPhoto['author'])) ?>', '<?= htmlspecialchars(addslashes($pPhoto['content'])) ?>')">
                <img src="<?= htmlspecialchars($pPhoto['url']) ?>" alt="Foto do comprador">
                <div class="photo-carousel-author"><?= htmlspecialchars($pPhoto['author']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- SEÇÃO AVALIAÇÕES -->
      <div class="user-media-reviews-box" id="directUserReviewsList">
        <h3 class="reviews-section-title">Avaliações</h3>

        <!-- Dropdown Ordenar Por -->
        <div class="sort-reviews-container">
          <label class="sort-reviews-label">Ordenar por</label>
          <div class="sort-select-box">
            <span>Mais úteis</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#555555" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
        </div>

        <?php if (!empty($dbReviews)): ?>
          <?php foreach ($dbReviews as $index => $rev): ?>
            <div class="review-card-item <?= $index >= 10 ? 'extra-review-item' : '' ?>" style="<?= $index >= 10 ? 'display: none;' : '' ?>">
              <div class="reviewer-name" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span><?= htmlspecialchars($rev['author']) ?></span>
                <?php if (!empty($rev['images'])): ?>
                  <span style="background: #e8f5e9; color: #168019; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#168019" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>Avaliação c/ Foto</span>
                  </span>
                <?php endif; ?>
              </div>
              
              <div class="reviewer-meta-row">
                <div class="rating-stars-list">
                  <?php for ($i = 0; $i < intval($rev['rating'] ?: 5); $i++): ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                  <?php endfor; ?>
                </div>
                <span class="review-date-text"><?= htmlspecialchars($rev['review_date']) ?></span>
              </div>

              <p class="review-body-text">
                <?= nl2br(htmlspecialchars($rev['content'])) ?>
              </p>

              <?php if (!empty($rev['images'])): ?>
                <div class="review-photos-grid">
                  <?php foreach ($rev['images'] as $rImg): ?>
                    <div class="review-photo-thumb-card" onclick="openReviewPhotoModal('<?= htmlspecialchars(addslashes($rImg['image_url'])) ?>', '<?= htmlspecialchars(addslashes($rev['author'])) ?>', '<?= htmlspecialchars(addslashes($rev['content'])) ?>')">
                      <img src="<?= htmlspecialchars($rImg['image_url']) ?>" alt="Foto da Avaliação">
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php if (count($dbReviews) > 10): ?>
            <div style="text-align: center; margin-top: 24px; margin-bottom: 24px;">
              <button id="btnShowMoreReviews" onclick="toggleMoreReviews()" style="background: none; border: none; color: #003399; font-size: 16px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; font-family: inherit;">
                <span>Ver mais avaliações</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <!-- Avaliações Padrão de Exemplo -->
          <div class="review-card-item">
            <div class="reviewer-name">Francisco</div>
            <div class="reviewer-meta-row">
              <div class="rating-stars-list">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#e67e22"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
              </div>
              <span class="review-date-text">Avaliado em 12 de junho de 2026</span>
            </div>
            <p class="review-body-text">
              Excelente produto! Ótimo custo-benefício, entregou super rápido e atendeu totalmente as minhas expectativas.
            </p>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Similares ao que você está vendo -->
    <?php if (!empty($similarProducts)): ?>
    <section class="sponsored-section" style="margin-top: 24px;">
      <h2 class="sponsored-title">Similares ao que você está vendo</h2>
      <div class="sponsored-carousel">
        <?php foreach ($similarProducts as $spon):
          $pixPrice   = !empty($spon['pix_price'])          ? (float)$spon['pix_price']          : (float)$spon['price'];
          $regPrice   = !empty($spon['regular_price'])       ? (float)$spon['regular_price']       : 0;
          $discount   = !empty($spon['discount_percentage']) ? (int)$spon['discount_percentage']   : 0;
          $rating     = !empty($spon['rating'])              ? (float)$spon['rating']              : 4.0;
          $reviewsCnt = !empty($spon['reviews_count'])       ? (int)$spon['reviews_count']         : 0;
          $hasFrete   = !empty($spon['free_shipping'])  || ($pixPrice < 300);
          $imgSrc     = !empty($spon['main_image'])          ? htmlspecialchars($spon['main_image']) : 'assets/images/product_aoc_main.png';
        ?>
          <a href="index.php?id=<?= $spon['id'] ?>" class="sponsored-card">
            <div class="sponsored-card-img-wrap">
              <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($spon['title']) ?>" loading="lazy">
              <div class="sponsored-card-badge">COMPRE E<br>RETIRE EM</div>
            </div>
            <div class="sponsored-card-body">
              <div class="sponsored-card-title"><?= htmlspecialchars($spon['title']) ?></div>
              <div class="sponsored-card-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <svg viewBox="0 0 24 24" fill="<?= $s <= round($rating) ? '#e67e22' : '#ddd' ?>"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <?php endfor; ?>
                <?php if ($reviewsCnt > 0): ?><span>(<?= $reviewsCnt ?>)</span><?php endif; ?>
              </div>
              <?php if ($regPrice > 0 && $regPrice > $pixPrice): ?>
                <div class="sponsored-card-old-price">
                  R$ <?= number_format($regPrice, 2, ',', '.') ?>
                  <?php if ($discount > 0): ?><span class="sponsored-card-discount">-<?= $discount ?>%</span><?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="sponsored-card-price">R$ <?= number_format($pixPrice, 2, ',', '.') ?></div>
              <?php if ($pixPrice > 0): ?><div class="sponsored-card-pix">No Pix</div><?php endif; ?>
            </div>
            <?php if ($hasFrete): ?><div class="sponsored-card-frete">FRETE GRÁTIS</div><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </main>

  <!-- BARRA FIXA INFERIOR -->
  <div class="sticky-bottom-bar" id="stickyBottomBar">
    <div class="sticky-product-info">
      <img src="<?= htmlspecialchars($mainImage) ?>" alt="Mini thumbnail">
      <div class="sticky-price-details">
        <div class="sticky-price-row">
          <strong class="sticky-main-price">R$ <span id="stickyPixPrice"><?= number_format($product['pix_price'], 2, ',', '.') ?></span></strong>
          <span class="sticky-pix-label">No Pix</span>
        </div>
        <div class="sticky-delivery-sub" id="stickyDeliveryText">
          Receba até <strong>11 de setembro</strong> com <strong>Frete Grátis</strong>.
        </div>
      </div>
    </div>
    <button class="btn-sticky-buy" onclick="handleBuy('comprar_sticky')">Comprar</button>
  </div>

  <!-- MODAL TELA INTEIRA: MEUS PEDIDOS -->
  <div class="full-page-orders-modal" id="myOrdersModalOverlay" style="display: none;">
    <div class="orders-modal-header">
      <div style="display: flex; align-items: center; gap: 12px;">
        <button type="button" class="orders-modal-close-btn" onclick="closeMyOrdersModal()" aria-label="Voltar">←</button>
        <div>
          <div class="orders-modal-title">Meus Pedidos</div>
          <div style="font-size: 12px; color: #cbd5e1; font-weight: 400;">Acompanhe suas compras e status de entrega</div>
        </div>
      </div>
      <button type="button" class="orders-modal-close-btn" onclick="closeMyOrdersModal()" aria-label="Fechar">✕</button>
    </div>

    <div style="max-width: 600px; margin: 0 auto; width: 100%; padding: 20px 16px; flex: 1;">
      <div id="myOrdersListContainer"></div>
    </div>
  </div>

  <!-- MODAL DE COMPARTILHAMENTO NAS REDES SOCIAIS E CÓPIA DE LINK -->
  <div class="share-modal-overlay" id="shareModalOverlay" onclick="closeShareModal(event)">
    <div class="share-modal-card" onclick="event.stopPropagation()">
      <div class="share-modal-header">
        <span>Compartilhar Produto</span>
        <button type="button" class="share-close-btn" onclick="closeShareModal(event)">✕</button>
      </div>

      <div class="share-modal-body">
        <p class="share-product-title"><?= htmlspecialchars($product['title']) ?></p>

        <div class="share-options-grid">
          <!-- WhatsApp -->
          <a href="#" id="shareWhatsappLink" target="_blank" class="share-opt-btn whatsapp-bg">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.146 4.186 4.254-1.116z"/></svg>
            <span>WhatsApp</span>
          </a>

          <!-- Facebook -->
          <a href="#" id="shareFacebookLink" target="_blank" class="share-opt-btn facebook-bg">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            <span>Facebook</span>
          </a>

          <!-- X / Twitter -->
          <a href="#" id="shareTwitterLink" target="_blank" class="share-opt-btn twitter-bg">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            <span>X (Twitter)</span>
          </a>

          <!-- Telegram -->
          <a href="#" id="shareTelegramLink" target="_blank" class="share-opt-btn telegram-bg">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm5.56 8.16l-2.03 9.56c-.15.68-.55.84-1.12.52l-3.1-2.29-1.5 1.44c-.16.16-.3.3-.61.3l.22-3.17 5.77-5.21c.25-.22-.05-.34-.39-.12l-7.14 4.49-3.07-.96c-.67-.21-.68-.67.14-.99l12.01-4.63c.56-.21 1.04.13.88.96z"/></svg>
            <span>Telegram</span>
          </a>
        </div>

        <!-- Copiar Link Box -->
        <div class="copy-link-container">
          <input type="text" id="shareUrlInput" class="copy-link-input" readonly>
          <button type="button" class="btn-copy-link" onclick="copyShareUrl()">Copiar Link</button>
        </div>

        <div id="copySuccessMsg" class="copy-success-toast" style="display: none;">
          ✓ Link copiado para a área de transferência!
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL DE CARACTERÍSTICAS COMPLETO DA CASAS BAHIA (MEDIA_1787235422594.PNG) -->
  <div class="specs-modal-overlay" id="specsModalOverlay" onclick="closeSpecsModal(event)">
    <div class="specs-modal-card" onclick="event.stopPropagation()">
      <div class="specs-modal-header">
        <span>Características</span>
        <button type="button" class="specs-close-btn" onclick="closeSpecsModal(event)">✕</button>
      </div>

      <div class="specs-modal-body">
        <?php if (!empty($specs)): ?>
          <?php foreach ($specs as $sp): ?>
            <div class="specs-modal-group">
              <div class="specs-modal-name"><?= htmlspecialchars($sp['spec_name']) ?></div>
              <div class="specs-modal-val"><?= nl2br(htmlspecialchars($sp['spec_value'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="specs-modal-group">
            <div class="specs-modal-name">Marca</div>
            <div class="specs-modal-val"><?= htmlspecialchars($product['brand']) ?></div>
          </div>
          <div class="specs-modal-group">
            <div class="specs-modal-name">Código do Item</div>
            <div class="specs-modal-val"><?= htmlspecialchars($product['item_code']) ?></div>
          </div>
          <div class="specs-modal-group">
            <div class="specs-modal-name">Garantia do Fabricante</div>
            <div class="specs-modal-val">12 Meses</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- MODAL LIGHTBOX DE FOTO DA AVALIAÇÃO DO CLIENTE -->
  <div class="review-lightbox-overlay" id="reviewLightboxOverlay" onclick="closeReviewPhotoModal()">
    <div class="review-lightbox-card" onclick="event.stopPropagation()">
      <button type="button" class="review-lightbox-close" onclick="closeReviewPhotoModal()">✕</button>
      <img id="lightboxImage" src="" alt="Foto expandida da avaliação">
      <div class="review-lightbox-info">
        <div id="lightboxAuthor" style="font-weight: 800; color: #ffffff; font-size: 16px; margin-bottom: 4px;"></div>
        <div id="lightboxContent" style="color: #cbd5e1; font-size: 13px; line-height: 1.4;"></div>
      </div>
    </div>
  </div>

  <script>
    function openReviewPhotoModal(imgUrl, author, content) {
      var overlay = document.getElementById('reviewLightboxOverlay');
      var img = document.getElementById('lightboxImage');
      var authorEl = document.getElementById('lightboxAuthor');
      var contentEl = document.getElementById('lightboxContent');

      if (img) img.src = imgUrl;
      if (authorEl) authorEl.textContent = author || '';
      if (contentEl) contentEl.textContent = content || '';
      if (overlay) overlay.style.display = 'flex';
    }

    function closeReviewPhotoModal() {
      var overlay = document.getElementById('reviewLightboxOverlay');
      if (overlay) overlay.style.display = 'none';
    }



    function handleProductShare(e) {
      if (e) e.preventDefault();
      openShareModal();
    }

    function openShareModal() {
      var modal = document.getElementById('shareModalOverlay');
      var urlInput = document.getElementById('shareUrlInput');
      var prodUrl = window.location.href;
      var prodTitle = encodeURIComponent("<?= htmlspecialchars(addslashes($product['title'])) ?>");
      var encodedUrl = encodeURIComponent(prodUrl);

      if (urlInput) urlInput.value = prodUrl;

      document.getElementById('shareWhatsappLink').href = `https://api.whatsapp.com/send?text=${prodTitle}%20${encodedUrl}`;
      document.getElementById('shareFacebookLink').href = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
      document.getElementById('shareTwitterLink').href = `https://twitter.com/intent/tweet?text=${prodTitle}&url=${encodedUrl}`;
      document.getElementById('shareTelegramLink').href = `https://t.me/share/url?url=${encodedUrl}&text=${prodTitle}`;

      if (modal) modal.style.display = 'flex';
    }

    function closeShareModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('shareModalOverlay');
      if (modal) modal.style.display = 'none';
      var toast = document.getElementById('copySuccessMsg');
      if (toast) toast.style.display = 'none';
    }

    function copyShareUrl() {
      var urlInput = document.getElementById('shareUrlInput');
      if (!urlInput) return;
      
      urlInput.select();
      urlInput.setSelectionRange(0, 99999);
      
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(urlInput.value).then(showCopySuccess).catch(fallbackCopy);
      } else {
        fallbackCopy();
      }
    }

    function fallbackCopy() {
      try {
        document.execCommand('copy');
        showCopySuccess();
      } catch (err) {
        alert('Link do produto: ' + window.location.href);
      }
    }

    function showCopySuccess() {
      var toast = document.getElementById('copySuccessMsg');
      if (toast) {
        toast.style.display = 'block';
        setTimeout(function() {
          toast.style.display = 'none';
        }, 2500);
      }
    }

    function selectProductVariation(btn, val) {
      document.querySelectorAll('.variation-btn').forEach(function(b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var label = document.getElementById('selectedVariationText');
      if (label) label.textContent = val;

      var prodId = "<?= $product['id'] ?>";
      var varName = "<?= htmlspecialchars(addslashes($product['variation_name'] ?? 'Variação')) ?>";
      try {
        localStorage.setItem('selectedVariation_' + prodId, JSON.stringify({ name: varName, value: val }));
      } catch (e) {}
    }

    function checkUserSession() {
      var sessionRaw = localStorage.getItem('userSession');
      var titleEl = document.getElementById('navUserTitle');
      var subEl = document.getElementById('navUserSubtitle');
      var btnEl = document.getElementById('btnNavLogin');

      if (sessionRaw) {
        try {
          var session = JSON.parse(sessionRaw);
          if (session && session.loggedIn) {
            var displayCpf = session.cpf || '';

            if (titleEl) titleEl.textContent = 'Olá, cliente';
            if (subEl) subEl.textContent = 'CPF: ' + displayCpf + ' · Conta ativa';
            if (btnEl) {
              btnEl.textContent = 'Sair da conta';
              btnEl.style.borderColor = '#e30613';
              btnEl.style.color = '#e30613';
            }
            return true;
          }
        } catch(e) {}
      }

      if (titleEl) titleEl.textContent = 'Olá, visitante';
      if (subEl) subEl.textContent = 'Acesse sua conta ou cadastre-se para ver seus pedidos';
      if (btnEl) {
        btnEl.textContent = 'Entre ou Cadastre-se';
        btnEl.style.borderColor = '#003399';
        btnEl.style.color = '#003399';
      }
      return false;
    }

    window.addEventListener('storage', function(e) {
      if (e.key === 'userSession') checkUserSession();
    });

    function handleUserHeaderClick() {
      if (checkUserSession()) {
        openMyAccountModal();
      } else {
        openLoginModal();
      }
    }

    function handleNavLoginClick() {
      var sessionRaw = localStorage.getItem('userSession');
      if (sessionRaw) {
        try {
          var session = JSON.parse(sessionRaw);
          if (session && session.loggedIn) {
            localStorage.removeItem('userSession');
            localStorage.removeItem('userCep');
            localStorage.removeItem('userCityState');
            localStorage.removeItem('savedAddressData');
            localStorage.removeItem('last_order_id');
            checkUserSession();
            if (typeof checkUserLocation === 'function') checkUserLocation();
            alert('Você desconectou da sua conta.');
            return;
          }
        } catch(e) {}
      }
      openLoginModal();
    }

    async function openMyOrdersModal(e) {
      if (e) e.preventDefault();
      if (typeof closeNavDrawer === 'function') closeNavDrawer();
      
      var sessionRaw = localStorage.getItem('userSession');
      var isLogged = false;
      if (sessionRaw) {
        try {
          var session = JSON.parse(sessionRaw);
          if (session && session.loggedIn && session.cpf) isLogged = true;
        } catch(e) {}
      }

      if (!isLogged) {
        window.pendingOpenOrdersAfterLogin = true;
        openLoginModal();
        return;
      }

      var modal = document.getElementById('myOrdersModalOverlay');
      if (modal) {
        modal.style.display = 'flex';
      }

      var listContainer = document.getElementById('myOrdersListContainer');
      if (!listContainer) return;

      listContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #003399; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 10px;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="12"/></svg>Buscando seus pedidos...</div>';

      var userCpf = '';
      try {
        var sessionRaw = localStorage.getItem('userSession');
        if (sessionRaw) {
          var session = JSON.parse(sessionRaw);
          if (session && session.cpf) userCpf = session.cpf;
        }
      } catch(e) {}

      try {
        const res = await fetch('api/get_my_orders.php?cpf=' + encodeURIComponent(userCpf));
        const data = await res.json();

        if (data && data.orders && data.orders.length > 0) {
          var html = '';
          data.orders.forEach(function(ord) {
            var st = (ord.payment_status || '').toLowerCase();
            var isPaid = (st === 'pago' || st === 'pagamento confirmado' || st === 'aprovado' || st.indexOf('pago') !== -1 || st.indexOf('aprovad') !== -1);

            var statusBadge = isPaid
              ? `<span style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Pagamento Aprovado</span>`
              : `<span style="background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Aguardando Pagamento</span>`;

            var pixButton = !isPaid 
              ? `<a href="pix.php?order_id=${ord.id}" style="display: inline-flex; align-items: center; gap: 6px; background: #168019; color: #ffffff; font-weight: 800; font-size: 13px; padding: 10px 18px; border-radius: 6px; text-decoration: none; margin-top: 12px; box-shadow: 0 2px 6px rgba(22, 128, 25, 0.25);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>PAGAR COM PIX</a>`
              : '';

            var priceFormatted = parseFloat(ord.product_price).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            html += `
              <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px;">
                  <div>
                    <strong style="font-size: 15px; color: #003399;">Pedido #${ord.id}</strong>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">${ord.created_at || ''}</div>
                  </div>
                  <div>${statusBadge}</div>
                </div>

                <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 12px;">
                  <div style="width: 76px; height: 76px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border-radius: 8px; overflow: hidden;">
                    <img src="${ord.main_image || 'assets/images/product_aoc_main.png'}" alt="Foto do Produto" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                  </div>
                  <div>
                    <div style="font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.3; margin-bottom: 4px;">${ord.product_title || 'Produto Casas Bahia'}</div>
                    <div style="font-size: 16px; font-weight: 900; color: #003399;">R$ ${priceFormatted}</div>
                  </div>
                </div>

                <div style="font-size: 12px; color: #475569; background: #f8fafc; padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                  <strong>Endereço de entrega:</strong> ${ord.shipping_address || 'Cadastrado no pedido'}
                </div>
                ${pixButton}
              </div>
            `;
          });
          listContainer.innerHTML = html;
        } else {
          showEmptyOrdersView(listContainer);
        }
      } catch(e) {
        showEmptyOrdersView(listContainer);
      }
    }

    function showEmptyOrdersView(container) {
      container.innerHTML = `
        <div style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 48px 24px; text-align: center; margin-top: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
          <div style="width: 70px; height: 70px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px auto;">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
              <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
              <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
          </div>
          <h3 style="font-size: 20px; font-weight: 800; color: #003399; margin-bottom: 8px;">Você ainda não possui pedidos</h3>
          <p style="font-size: 14px; color: #64748b; margin-bottom: 24px; line-height: 1.4;">Confira nossas ofertas imperdíveis e faça sua primeira compra agora mesmo!</p>
          <button onclick="closeMyOrdersModal()" style="background: #003399; color: #ffffff; font-weight: 800; font-size: 15px; padding: 14px 28px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,51,153,0.2);">VOLTAR ÀS COMPRAS</button>
        </div>
      `;
    }

    function closeMyOrdersModal() {
      var modal = document.getElementById('myOrdersModalOverlay');
      if (modal) modal.style.display = 'none';
    }

    function formatCepDigits(val) {
      var v = val.replace(/\D/g, '');
      if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
      return v;
    }

    function handlePdpCepInput(input) {
      var formatted = formatCepDigits(input.value);
      input.value = formatted;
      var rawDigits = formatted.replace(/\D/g, '');
      if (rawDigits.length === 8) {
        calculatePdpFreight(rawDigits);
      }
    }

    function handlePdpCepSubmit(e) {
      if (e) e.preventDefault();
      var input = document.getElementById('pdpCepInput');
      if (input) {
        var rawDigits = input.value.replace(/\D/g, '');
        if (rawDigits.length === 8) {
          calculatePdpFreight(rawDigits);
        } else {
          alert('Por favor, informe um CEP válido com 8 dígitos.');
        }
      }
    }

    async function calculatePdpFreight(rawCep) {
      if (!rawCep || rawCep.length !== 8) return;

      var formattedCep = rawCep.substring(0, 5) + '-' + rawCep.substring(5, 8);
      try { localStorage.setItem('userCep', formattedCep); } catch(e) {}

      var cityStateText = 'Sua região';

      try {
        let data = null;
        try {
          const resLocal = await fetch('api/cep.php?cep=' + rawCep);
          if (resLocal.ok) data = await resLocal.json();
        } catch (err) {}

        if (!data || data.erro) {
          const resVia = await fetch('https://viacep.com.br/ws/' + rawCep + '/json/');
          data = await resVia.json();
        }

        if (data && !data.erro) {
          if (data.localidade && data.uf) {
            cityStateText = data.localidade + '/' + data.uf;
          } else if (data.localidade) {
            cityStateText = data.localidade;
          }
        }
      } catch(err) {}

      var cityElem = document.getElementById('pdpShippingCity');
      if (cityElem) cityElem.textContent = cityStateText;

      var deadlineElem = document.getElementById('pdpShippingDeadline');
      if (deadlineElem) {
        var d = new Date();
        d.setDate(d.getDate() + 3);
        var days = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
        var months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        deadlineElem.textContent = 'até ' + d.getDate() + ' de ' + months[d.getMonth()] + ', ' + days[d.getDay()];
      }

      var resultBox = document.getElementById('pdpShippingResult');
      if (resultBox) resultBox.style.display = 'block';

      var cepBar = document.getElementById('cepTextContainer');
      if (cepBar) {
        cepBar.innerHTML = `<span style="font-weight:700; color:#003399;">Receber em ${cityStateText}</span>`;
      }
    }

    function useMyLocation() {
      if (!navigator.geolocation) {
        alert('Geolocalização não suportada no seu navegador. Digite seu CEP.');
        return;
      }
      navigator.geolocation.getCurrentPosition(async function(pos) {
        var lat = pos.coords.latitude;
        var lon = pos.coords.longitude;
        try {
          const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`);
          const data = await res.json();
          const addr = data.address || {};
          var rawCep = (addr.postcode || '').replace(/\D/g, '');
          if (rawCep.length === 8) {
            var formatted = rawCep.substring(0, 5) + '-' + rawCep.substring(5, 8);
            var input = document.getElementById('pdpCepInput');
            if (input) input.value = formatted;
            calculatePdpFreight(rawCep);
          } else {
            var city = addr.city || addr.town || addr.municipality || 'Garopaba';
            var state = addr.state || 'SC';
            var cityElem = document.getElementById('pdpShippingCity');
            if (cityElem) cityElem.textContent = city + '/' + state;
            var resultBox = document.getElementById('pdpShippingResult');
            if (resultBox) resultBox.style.display = 'block';
          }
        } catch(e) {
          var resultBox = document.getElementById('pdpShippingResult');
          if (resultBox) resultBox.style.display = 'block';
        }
      }, function(err) {
        alert('Permissão de localização negada. Por favor, digite seu CEP.');
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      checkUserSession();
      if (typeof checkUserLocation === 'function') checkUserLocation();

      var savedCep = localStorage.getItem('userCep');
      if (savedCep) {
        var input = document.getElementById('pdpCepInput');
        if (input) input.value = savedCep;
        var rawDigits = savedCep.replace(/\D/g, '');
        if (rawDigits.length === 8) {
          calculatePdpFreight(rawDigits);
        }
      } else {
        // Aguarda o usuário digitar o CEP
      }

      var urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('open_orders') === '1') {
        setTimeout(function() {
          if (typeof openMyOrdersModal === 'function') {
            openMyOrdersModal();
          }
        }, 350);
      }
    });

    function sendActivityPulse(pageName) {
      try { fetch('api/tracker.php?page=' + encodeURIComponent(pageName)); } catch (e) {}
    }
    sendActivityPulse('index.php');
    setInterval(function() { sendActivityPulse('index.php'); }, 8000);
  </script>
  <script src="assets/js/app.js?v=<?= time() ?>"></script>

  <!-- ===== MODAL: MEUS FAVORITOS ===== -->
  <div class="fav-modal-overlay" id="favModalOverlay">
    <div class="fav-modal-header">
      <span class="fav-modal-title">Meus Favoritos</span>
      <button class="fav-modal-close" onclick="closeFavModal()">&#x2715;</button>
    </div>
    <div class="fav-modal-body" id="favModalBody"></div>
  </div>

  <script>
    /* ===== SISTEMA DE FAVORITOS (localStorage) — index.php ===== */
    var FAV_KEY = 'cb_favorites';

    function getFavorites() {
      try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch(e) { return []; }
    }
    function saveFavorites(favs) {
      try { localStorage.setItem(FAV_KEY, JSON.stringify(favs)); } catch(e) {}
    }

    function isUserLogged() {
      var sessionRaw = localStorage.getItem('userSession');
      if (sessionRaw) {
        try {
          var session = JSON.parse(sessionRaw);
          if (session && session.loggedIn && session.cpf) return true;
        } catch(e) {}
      }
      return false;
    }

    function toggleFavorite(id, title, price, img) {
      if (!isUserLogged()) {
        if (typeof closeNavDrawer === 'function') closeNavDrawer();
        window.pendingFavProduct = { id: id, title: title, price: price, img: img };
        window.pendingOpenFavAfterLogin = true;
        if (typeof openLoginModal === 'function') openLoginModal();
        return false;
      }
      var favs = getFavorites();
      var idx = favs.findIndex(function(f){ return f.id == id; });
      if (idx >= 0) { favs.splice(idx, 1); }
      else { favs.push({ id: id, title: title, price: price, img: img }); }
      saveFavorites(favs);
      updateAllFavButtons();
      updateFavBadge();
      return true;
    }

    function toggleFavoritePDP(id, title, price, img) {
      var res = toggleFavorite(id, title, price, img);
      if (!res) return;
      /* feedback visual no botão da galeria */
      var btn = document.getElementById('fav-pdp-' + id);
      if (!btn) return;
      var isFav = getFavorites().some(function(f){ return f.id == id; });
      if (isFav) {
        btn.classList.add('fav-active');
        btn.querySelector('svg').setAttribute('fill', '#e30613');
        btn.querySelector('svg').setAttribute('stroke', '#e30613');
      } else {
        btn.classList.remove('fav-active');
        btn.querySelector('svg').setAttribute('fill', 'none');
        btn.querySelector('svg').setAttribute('stroke', '#003399');
      }
    }

    function updateAllFavButtons() {
      var favs = getFavorites();
      var ids = favs.map(function(f){ return f.id; });
      /* botões com id="fav-*" nas páginas de listagem */
      document.querySelectorAll('[id^="fav-"]').forEach(function(btn) {
        var parts = btn.id.split('-');
        var pid = parseInt(parts[parts.length - 1]);
        if (ids.indexOf(pid) >= 0) {
          btn.classList.add('active');
          var svg = btn.querySelector('svg');
          if (svg) { svg.setAttribute('fill', '#e30613'); svg.setAttribute('stroke', '#e30613'); }
        } else {
          btn.classList.remove('active');
          var svg = btn.querySelector('svg');
          if (svg) { svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', '#003399'); }
        }
      });
    }

    function updateFavBadge() {
      var favs = getFavorites();
      var badge = document.getElementById('favBadge');
      if (!badge) return;
      var count = favs.length;
      badge.textContent = count > 99 ? '99+' : count;
      if (count > 0) { badge.classList.add('show'); }
      else { badge.classList.remove('show'); }
    }

    function openFavModal() {
      if (typeof closeNavDrawer === 'function') closeNavDrawer();
      if (!isUserLogged()) {
        window.pendingOpenFavAfterLogin = true;
        if (typeof openLoginModal === 'function') openLoginModal();
        return;
      }
      var overlay = document.getElementById('favModalOverlay');
      if (overlay) { overlay.classList.add('open'); renderFavModal(); }
    }
    function closeFavModal() {
      var overlay = document.getElementById('favModalOverlay');
      if (overlay) overlay.classList.remove('open');
    }

    function renderFavModal() {
      var body = document.getElementById('favModalBody');
      if (!body) return;
      var favs = getFavorites();
      if (favs.length === 0) {
        body.innerHTML = '<div class="fav-empty-state">' +
          '<svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>' +
          '<p>Você ainda não tem favoritos.<br>Clique no <strong>♡</strong> em qualquer produto!</p></div>';
        return;
      }
      var html = '<div class="fav-grid">';
      favs.forEach(function(f) {
        var priceFormatted = 'R$ ' + parseFloat(f.price).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
        html += '<div class="fav-item-card">' +
          '<button class="fav-item-remove" onclick="removeFavorite(' + f.id + ')" title="Remover">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e30613" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
          '</button>' +
          '<img class="fav-item-img" src="' + escHtml(f.img) + '" alt="' + escHtml(f.title) + '" onerror="this.src=\'assets/images/product_aoc_main.png\'">' +
          '<div class="fav-item-title">' + escHtml(f.title) + '</div>' +
          '<div class="fav-item-price">' + priceFormatted + '</div>' +
          '<div class="fav-item-pix">No Pix</div>' +
          '<button class="fav-item-buy" onclick="window.location.href=\'index.php?id=' + f.id + '\'">Ver Produto</button>' +
          '</div>';
      });
      html += '</div>';
      body.innerHTML = html;
    }

    function removeFavorite(id) {
      var favs = getFavorites().filter(function(f){ return f.id != id; });
      saveFavorites(favs);
      updateAllFavButtons();
      updateFavBadge();
      renderFavModal();
    }

    function escHtml(str) {
      return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    document.addEventListener('DOMContentLoaded', function() {
      updateAllFavButtons();
      updateFavBadge();
    });
  </script>

</body>
</html>
