<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/domain_guard.php';
checkDomainAccess();
require_once __DIR__ . '/includes/pixel_tracker.php';

$db = getDBConnection();

// 1. Buscar Banners da Home cadastrados no MySQL
$banners = [];
try {
    $stmtB = $db->query("SELECT * FROM home_banners ORDER BY sort_order ASC, id ASC");
    $banners = $stmtB->fetchAll();
} catch (Exception $e) {}

// Caso não haja banners cadastrados, gera 10 slides padrão com as 10 bolinhas idênticas à foto do usuário
if (empty($banners)) {
    for ($i = 1; $i <= 10; $i++) {
        $banners[] = [
            'id' => $i,
            'image_url' => 'gradient',
            'link_url' => '#',
            'sort_order' => $i
        ];
    }
}

// 1.5 Buscar Categorias da Home (Ícones / GIFs)
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

// 2. Buscar todos os produtos cadastrados no MySQL do Painel Admin
$products = [];
try {
    $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {}

// Fallback de produtos caso o banco esteja vazio
if (empty($products)) {
    $products = [
        [
            'id' => 1,
            'title' => 'Fritadeira Air Fryer Oven Mondial AFON-12L-FG 12L Digital 2000W Cinza - 110V / 220V',
            'brand' => 'Mondial',
            'regular_price' => 571.00,
            'pix_price' => 499.00,
            'discount_percentage' => 13,
            'rating' => 4.9,
            'reviews_count' => 151,
            'max_installments' => 10,
            'installment_value' => 49.90
        ]
    ];
}

// 3. Mapear imagem principal de cada produto
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Casas Bahia: Ofertas de TV, Celulares, Eletrodomésticos e Móveis</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <!-- OPEN GRAPH / SOCIAL SHARE (WHATSAPP, FACEBOOK, TELEGRAM, TWITTER) -->
  <?php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $siteHost = $_SERVER['HTTP_HOST'] ?? 'meucarrinho.online';
    $baseUrl = $protocol . $siteHost;
    $homeOgImage = $baseUrl . '/assets/images/casasbahia_home_share.jpg';
    $pageOgTitle = "Casas Bahia: Ofertas de TV, Celulares, Eletrodomésticos e Móveis";
    $pageOgDesc = "Aproveite as melhores ofertas com frete grátis e desconto exclusivo no Pix nas Casas Bahia. Compre agora!";
    $pageOgUrl = $baseUrl . '/home.php';
  ?>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Casas Bahia">
  <meta property="og:title" content="<?= htmlspecialchars($pageOgTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($pageOgDesc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($homeOgImage) ?>">
  <meta property="og:image:secure_url" content="<?= htmlspecialchars($homeOgImage) ?>">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="783">
  <meta property="og:image:height" content="392">
  <meta property="og:url" content="<?= htmlspecialchars($pageOgUrl) ?>">
    <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageOgTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($pageOgDesc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($homeOgImage) ?>">
  <?php renderFacebookPixel('PageView'); ?>
  <style>
    :root {
      --cb-blue-primary: #003399;
      --cb-blue-dark: #001a4d;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-green-bg: #137e16;
      --cb-yellow: #ffb800;
      --cb-gray-bg: #f4f6f8;
      --cb-gray-border: #e2e8f0;
      --cb-text-dark: #1e293b;
      --cb-text-muted: #64748b;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family); background-color: #ffffff; color: var(--cb-text-dark); line-height: 1.4; padding-bottom: 0; -webkit-font-smoothing: antialiased; }
    a { color: inherit; text-decoration: none; }

    /* LINHA VERMELHA TOPO */
    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    /* CABEÇALHO E BARRA DE CEP (100% IDÊNTICO AO INDEX) */
    .main-header { background-color: #ffffff; border-bottom: 1px solid var(--cb-gray-border); position: relative; z-index: 10; }
    .header-top-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; }
    .header-left-group { display: flex; align-items: center; gap: 8px; }
    .brand-logo { font-size: 20px; font-weight: 800; color: var(--cb-blue-primary); letter-spacing: -0.5px; line-height: 1; display: flex; align-items: center; }
    .brand-logo span { color: var(--cb-red); }
    .header-actions { display: flex; gap: 14px; align-items: center; }
    .header-icon-btn { background: none; border: none; color: var(--cb-blue-primary); cursor: pointer; display: flex; align-items: center; padding: 2px; }
    
    .search-box-wrapper { padding: 0 16px 10px 16px; }
    .search-input-container { display: flex; align-items: center; background-color: #ffffff; border: 1px solid #d0d0d0; border-radius: 6px; padding: 8px 12px; }
    .search-input-container input { border: none; outline: none; width: 100%; font-size: 14px; font-weight: 400; color: var(--cb-text-dark); background: transparent; }
    .search-icon { color: #666666; display: flex; align-items: center; }

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

    /* ESTILOS DA TELA DE IDENTIFICAÇÃO (LOGIN) */
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

    /* ESTILOS DO MODAL DE CEP / FRETE */
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

    /* CATEGORIAS EM ÍCONES ARREDONDADOS (MAIORES) */
    .category-scroll-container { display: flex; gap: 16px; overflow-x: auto; padding: 18px 16px; background-color: #ffffff; scrollbar-width: none; }
    .category-scroll-container::-webkit-scrollbar { display: none; }
    .cat-item-card { display: flex; flex-direction: column; align-items: center; min-width: 86px; text-align: center; cursor: pointer; flex-shrink: 0; }
    .cat-icon-circle { width: 86px; height: 86px; border-radius: 26px; background: transparent; border: none; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.09); flex-shrink: 0; }
    .cat-icon-circle img, .cat-icon-circle video { width: 100% !important; height: 100% !important; object-fit: cover !important; border-radius: 26px !important; border: none !important; outline: none !important; display: block !important; transform: scale(1.05); }
    .cat-label { font-size: 12.5px; font-weight: 500; color: #003399; line-height: 1.25; text-align: center; white-space: nowrap; letter-spacing: -0.2px; }

    /* SLIDER HERO CARROSSEL (FULL BLEED SEM BORDAS LATERAIS) */
    .hero-carousel-container { position: relative; width: 100%; min-height: 180px; margin-bottom: 0; overflow: hidden; background: #ffffff; }
    .hero-carousel-track { display: flex; transition: transform 0.4s ease-in-out; width: 100%; background: #ffffff; }
    .hero-carousel-slide { width: 100%; flex: 0 0 100%; min-width: 100%; box-sizing: border-box; padding: 0; min-height: 180px; background: #ffffff; }
    .banner-slide-img { width: 100%; height: 180px; min-height: 180px; max-height: 280px; border-radius: 0; display: block; object-fit: cover; }
    @media (min-width: 768px) {
      .hero-carousel-container { min-height: 260px; }
      .hero-carousel-slide { min-height: 260px; }
      .banner-slide-img { height: 260px; min-height: 260px; max-height: 360px; }
    }

    /* BOLINHAS DE PAGINAÇÃO (FUNDO 100% BRANCO) */
    .hero-carousel-dots { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; margin-bottom: 20px; flex-wrap: wrap; max-width: 90%; margin-left: auto; margin-right: auto; background: #ffffff; padding: 4px 0; }
    .carousel-dot { width: 10px; height: 10px; border-radius: 50%; background-color: #cbd5e1; border: none; cursor: pointer; transition: all 0.25s ease; padding: 0; }
    .carousel-dot.active { background-color: #003399; transform: scale(1.25); width: 12px; height: 12px; }
    
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

    /* HEADER DAS OFERTAS RELÂMPAGO 100% IDÊNTICO À FOTO DA CASAS BAHIA */
    .relampago-header-container { display: flex; align-items: flex-start; justify-content: space-between; padding: 0 16px 10px 16px; width: 100%; }
    .relampago-header-left { display: flex; flex-direction: column; gap: 0; }
    .relampago-title { font-size: 16px; font-weight: 800; color: #003399; letter-spacing: -0.3px; display: flex; align-items: center; gap: 4px; white-space: nowrap; line-height: 1.2; }
    .relampago-subtext { font-size: 12px; color: #64748b; font-weight: 400; margin-top: 2px; }

    .relampago-timer-box { display: flex; align-items: center; gap: 4px; background-color: #003399; padding: 4px 8px; border-radius: 6px; flex-shrink: 0; }
    .timer-clock-icon { color: #ffffff; display: flex; align-items: center; justify-content: center; margin-right: 2px; }
    .timer-pill { background-color: #ffffff; color: #003399; font-size: 11px; font-weight: 800; padding: 3px 6px; border-radius: 4px; white-space: nowrap; display: flex; align-items: center; justify-content: center; line-height: 1; min-width: 34px; text-align: center; }

    /* CARD HORIZONTAL DE OFERTAS RELÂMPAGO (EXATAMENTE COMO NA FOTO 2 ENVIADA PELO USUÁRIO) */
    .relampago-card-horizontal { min-width: 310px; max-width: 340px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: row; align-items: center; gap: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); text-decoration: none; color: inherit; transition: transform 0.2s, box-shadow 0.2s; }
    .relampago-card-horizontal:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
    .relampago-card-img-box { width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .relampago-card-img-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .relampago-card-info { display: flex; flex-direction: column; flex: 1; }
    .relampago-card-title { font-size: 13px; font-weight: 600; color: #334155; line-height: 1.35; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 35px; }
    .relampago-card-price-old { font-size: 12px; color: #94a3b8; text-decoration: line-through; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .relampago-card-price-main { font-size: 18px; font-weight: 900; color: #003399; letter-spacing: -0.4px; }

    /* SEÇÕES DE OFERTAS PADRÃO */
    .section-block { margin-bottom: 28px; }
    .section-header-title { display: flex; align-items: center; justify-content: space-between; padding: 0 16px 12px 16px; }
    .section-h2 { font-size: 20px; font-weight: 900; color: #003399; letter-spacing: -0.3px; display: flex; align-items: center; gap: 8px; }

    /* CAROUSEL HORIZONTAL DE PRODUTOS */
    .products-horizontal-scroll { display: flex; gap: 14px; overflow-x: auto; padding: 4px 16px 16px 16px; scrollbar-width: none; }
    .products-horizontal-scroll::-webkit-scrollbar { display: none; }

    /* CARD DE PRODUTO VERTICAL CASAS BAHIA */
    .product-card-cb { min-width: 220px; max-width: 240px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s; }
    .product-card-cb:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.08); }

    .card-badge-exclusive { position: absolute; top: 10px; left: 10px; z-index: 2; background-color: #003399; color: #ffffff; font-size: 9px; font-weight: 900; padding: 3px 6px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.3px; border: 2px solid #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
    .card-sponsor-txt { font-size: 11px; color: #94a3b8; font-weight: 500; margin-bottom: 6px; }

    .card-img-wrapper { width: 100%; height: 160px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; position: relative; }
    .card-img-wrapper img { max-width: 100%; max-height: 100%; object-fit: contain; }

    .card-product-title { font-size: 13px; font-weight: 600; color: #334155; line-height: 1.35; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 35px; }

    /* AVALIAÇÃO EM ESTRELAS */
    .card-stars-row { display: flex; align-items: center; gap: 4px; margin-bottom: 10px; font-size: 12px; color: #eab308; font-weight: 700; }
    .card-stars-count { color: #64748b; font-size: 11px; font-weight: 500; }

    /* PREÇOS */
    .card-price-old-line { font-size: 12px; color: #94a3b8; text-decoration: line-through; display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
    .card-discount-tag { background-color: #15803d; color: #ffffff; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px; text-decoration: none; }

    .card-installment-txt { font-size: 12px; color: #475569; font-weight: 500; margin-bottom: 6px; line-height: 1.3; }
    .card-price-pix-bold { font-size: 20px; font-weight: 900; color: #003399; letter-spacing: -0.5px; margin-bottom: 2px; }
    .card-pix-label { font-size: 12px; color: #003399; font-weight: 700; margin-bottom: 10px; }

    /* TAGS DE FRETE / RETIRADA */
    .card-badge-frete { background-color: #ffb800; color: #000000; font-size: 10px; font-weight: 900; padding: 4px 8px; border-radius: 4px; text-align: center; text-transform: uppercase; margin-top: auto; display: inline-block; }
    .btn-card-retira { background-color: #003399; color: #ffffff; font-size: 11px; font-weight: 900; padding: 8px 12px; border-radius: 6px; text-align: center; text-transform: uppercase; width: 100%; border: none; cursor: pointer; margin-top: 6px; }

    /* FAIXA ANIMADA VERMELHA CURTA (PROMOÇÃO <-> CASAS BAHIA) */
    .card-badge-animated {
      background-color: #e30613;
      color: #ffffff;
      padding: 0 14px;
      border-radius: 6px;
      text-align: center;
      font-weight: 900;
      width: fit-content;
      max-width: 140px;
      margin: 6px auto 0 auto;
      overflow: hidden;
      height: 24px;
      position: relative;
      box-shadow: 0 2px 6px rgba(227, 6, 19, 0.3);
    }
    .animated-badge-track {
      display: flex;
      flex-direction: column;
      animation: swapBadgeText 3.5s infinite ease-in-out;
    }
    .animated-badge-track span {
      height: 24px;
      line-height: 24px;
      font-size: 11px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;
      color: #ffffff;
      display: block;
    }
    @keyframes swapBadgeText {
      0%, 42% { transform: translateY(0); }
      50%, 92% { transform: translateY(-24px); }
      100% { transform: translateY(0); }
    }

    /* RODAPÉ MÓVEL */
    .bottom-nav-bar { position: fixed; bottom: 0; left: 0; right: 0; background-color: #ffffff; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-around; padding: 8px 0; z-index: 200; box-shadow: 0 -4px 12px rgba(0,0,0,0.05); }
    .nav-bottom-item { display: flex; flex-direction: column; align-items: center; font-size: 10px; font-weight: 700; color: #64748b; gap: 3px; }
    .nav-bottom-item.active { color: #003399; }

    /* ===== SISTEMA DE FAVORITOS ===== */
    .fav-btn-card {
      position: absolute; top: 8px; right: 8px; z-index: 10;
      width: 34px; height: 34px; border-radius: 50%;
      background: rgba(255,255,255,0.92); border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      transition: transform 0.18s, box-shadow 0.18s;
    }
    .fav-btn-card:active { transform: scale(0.88); }
    .fav-btn-card svg { transition: fill 0.2s, stroke 0.2s; }
    .fav-btn-card.active svg { fill: #e30613; stroke: #e30613; }

    .fav-header-wrap { position: relative; display: inline-flex; align-items: center; }
    .fav-count-badge {
      position: absolute; top: -6px; right: -7px;
      background: #e30613; color: #fff;
      font-size: 9px; font-weight: 800; min-width: 16px; height: 16px;
      border-radius: 8px; display: none; align-items: center; justify-content: center;
      padding: 0 3px; line-height: 1; border: 1.5px solid #fff;
    }
    .fav-count-badge.show { display: flex; }

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
</head>
<body>

  <div class="top-red-line"></div>

  <!-- STICKY HEADER -->
  <header class="main-header">
    <div class="header-top-row">
      <div class="header-left-group">
        <!-- BOTÃO ÍCONE HAMBÚRGUER (≡ OU ||| DEITADOS) COM MESMO CLIQUE DO INDEX -->
        <button class="header-icon-btn" aria-label="Menu" onclick="openNavDrawer()">
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

    <!-- CAMPO DE BUSCA -->
    <div class="search-box-wrapper">
      <div class="search-input-container">
        <input type="text" placeholder="O que você tá procurando?" aria-label="Pesquisar">
        <span class="search-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666666" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </span>
      </div>
    </div>

    <!-- LOCALIZAÇÃO & CONSULTA DE FRETE (100% IDÊNTICO AO INDEX) -->
    <div class="cep-delivery-bar" id="cepBarToggle" onclick="openCepModal()">
      <div class="cep-left-info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
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

  <!-- NAVIGATION SIDE DRAWER (MENU HAMBÚRGUER LATERAL) -->
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

  <!-- OVERLAY / MODAL FULL-SCREEN: IDENTIFICAÇÃO (LOGIN 2 ETAPAS: CPF -> SENHA -> RECONHECER CLIENTE) -->
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

        <!-- ETAPA 1: DIGITAR CPF -->
        <div id="loginStepCpf">
          <h3 class="login-subtitle-heading">Entre ou cadastre-se</h3>
          <p class="login-subtext-desc">Para começar, digite seu CPF ou CNPJ no campo abaixo.</p>

          <form id="loginCpfForm" onsubmit="event.preventDefault(); goToPasswordStep();">
            <div class="form-group-login">
              <label for="loginCpfInput">CPF ou CNPJ</label>
              <input type="text" id="loginCpfInput" placeholder="" maxlength="18" oninput="formatLoginCpfMask(this)">
              <span class="login-example-text">Exemplo: 123.456.789-00 ou 12.345.678/0001-95</span>
              <div id="loginCpfError" class="modal-error-text" style="display: none; margin-top: 6px;"></div>
            </div>

            <button type="submit" class="btn-login-continue active" id="btnLoginContinue">Continuar</button>
          </form>

          <div class="social-login-divider">
            <span>Entre com suas redes sociais</span>
          </div>
        </div>

        <!-- ETAPA 2: DIGITAR SENHA -->
        <div id="loginStepPassword" style="display: none;">
          <h3 class="login-subtitle-heading">Informe sua senha</h3>
          <p class="login-subtext-desc">Digite a senha cadastrada para o CPF: <strong id="displayLoginCpf" style="color: #003399;"></strong></p>

          <form id="loginPasswordForm" onsubmit="event.preventDefault(); submitLoginSession();">
            <div class="form-group-login">
              <label for="loginPasswordInput">Sua Senha</label>
              <input type="password" id="loginPasswordInput" placeholder="Digite sua senha" style="width: 100%; height: 52px; padding: 0 16px; border: 1px solid #cccccc; border-radius: 8px; font-size: 16px; outline: none;">
              <div id="loginPassError" class="modal-error-text" style="display: none; margin-top: 6px;"></div>
            </div>

            <div style="margin-bottom: 24px;">
              <a href="#" onclick="alert('Um link de redefinição de senha foi enviado para seu e-mail.'); return false;" style="font-size: 13px; color: #003399; font-weight: 700; text-decoration: underline;">Esqueci minha senha</a>
            </div>

            <div style="display: flex; gap: 12px;">
              <button type="button" class="btn-login-back" style="margin-bottom: 0;" onclick="backToCpfStep()">Trocar CPF</button>
              <button type="submit" class="btn-login-continue active" style="margin-bottom: 0;">Entrar</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <!-- MODAL: Escolha sua Localização (CONSULTA DE FRETE / DIGITAR CEP) -->
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

        <form id="modalCepForm" onsubmit="handleModalCepSubmit(event)">
          <div style="margin-bottom: 16px;">
            <label for="modalCepInput" class="modal-input-label">Digite seu CEP</label>
            <input type="text" id="modalCepInput" placeholder="_____-___" maxlength="9" required oninput="formatCepMask(this)">
          </div>
          <button type="submit" class="btn-modal-confirm" id="btnModalConfirm">Confirmar</button>
          <div id="modalCepError" class="modal-error-text" style="display: none;"></div>
        </form>
      </div>
    </div>
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

  <!-- CATEGORIAS EM CARROSSEL DINÂMICAS (INVASÃO DE OFERTAS / GIFS, VÍDEOS E IMAGENS) -->
  <div class="category-scroll-container">
    <?php foreach ($categories as $cat): ?>
      <a href="categoria.php?cat=<?= urlencode($cat['slug']) ?>" class="cat-item-card" style="text-decoration: none; color: inherit;">
        <div class="cat-icon-circle">
          <?php if (!empty($cat['image_url'])): ?>
            <?php 
              $cUrl = (strpos($cat['image_url'], 'http') === 0 || strpos($cat['image_url'], 'data:') === 0) ? $cat['image_url'] : $cat['image_url'];
            ?>
            <?php if (isVideoMedia($cUrl)): ?>
              <video src="<?= htmlspecialchars($cUrl) ?>" autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover; border-radius: 22px; pointer-events: none; display: block; border: none;"></video>
            <?php else: ?>
              <img src="<?= htmlspecialchars($cUrl) ?>" alt="<?= htmlspecialchars($cat['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 22px; display: block; border: none;">
            <?php endif; ?>
          <?php else: ?>
            <div style="background: linear-gradient(135deg, #e30613 0%, #003399 100%); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; padding: 4px; border-radius: 22px;">
              <span style="font-size: 10px; font-weight: 900; line-height: 1.1; text-transform: uppercase;">INVASÃO</span>
              <span style="font-size: 9px; font-weight: 800; color: #ffb800; margin-top: 2px;">OFERTAS</span>
            </div>
          <?php endif; ?>
        </div>
        <span class="cat-label"><?= htmlspecialchars($cat['title']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- CARROSSEL HERO SLIDER COM PAGINAÇÃO DE BOLINHAS (IDÊNTICO À FOTO) -->
  <div class="hero-carousel-container">
    <div class="hero-carousel-track" id="heroCarouselTrack">
      <?php foreach ($banners as $idx => $banner): ?>
        <div class="hero-carousel-slide">
          <a href="<?= htmlspecialchars($banner['link_url'] ?? '#') ?>">
            <?php if (empty($banner['image_url']) || $banner['image_url'] === 'gradient'): ?>
              <!-- Banner Estilizado Casas Bahia Oficial -->
              <div style="background: linear-gradient(135deg, #003399 0%, #001a4d 100%); border-radius: 0; padding: 22px 20px; color: #ffffff; min-height: 155px; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; box-shadow: 0 4px 14px rgba(0,51,153,0.2);">
                <div style="font-size: 11px; font-weight: 900; background: #e30613; display: inline-block; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-bottom: 8px; width: fit-content;">PRORROGADO</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1.15; letter-spacing: -0.5px; margin-bottom: 8px;">INVASÃO DE OFERTAS & CUPONS</div>
                <div style="font-size: 15px; font-weight: 800; color: #ffb800; margin-bottom: 8px;">COM ATÉ R$ 2.000 DE DESCONTO NO PIX</div>
                <div style="font-size: 12px; opacity: 0.95;">OU DESCONTO NO CUPOM DE ATÉ <strong>50% OFF</strong> - USE O CUPOM: <strong>DESCONTO</strong></div>
              </div>
            <?php else: ?>
              <?php 
                $bUrl = (strpos($banner['image_url'], 'http') === 0 || strpos($banner['image_url'], 'data:') === 0) ? $banner['image_url'] : $banner['image_url'];
              ?>
              <?php if (isVideoMedia($bUrl)): ?>
                <video src="<?= htmlspecialchars($bUrl) ?>" autoplay loop muted playsinline preload="auto" class="banner-slide-img" style="border-radius: 0; object-fit: cover; display: block;"></video>
              <?php else: ?>
                <img src="<?= htmlspecialchars($bUrl) ?>" alt="Banner Promoção" class="banner-slide-img">
              <?php endif; ?>
            <?php endif; ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- BOLINHAS DE PAGINAÇÃO DO CARROSSEL (FUNDO 100% BRANCO) -->
  <div class="hero-carousel-dots" id="heroCarouselDots">
    <?php for ($d = 0; $d < count($banners); $d++): ?>
      <button class="carousel-dot <?= ($d === 0) ? 'active' : '' ?>" onclick="goToSlide(<?= $d ?>)" aria-label="Slide <?= $d + 1 ?>"></button>
    <?php endfor; ?>
  </div>

  <!-- SEÇÃO 1: OFERTAS RELÂMPAGO (HEADER E CARDS HORIZONTAIS 100% IDÊNTICOS À FOTO 2) -->
  <div class="section-block">
    <div class="relampago-header-container">
      <div class="relampago-header-left">
        <h2 class="relampago-title"><span>Ofertas Relâmpago</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="#e30613"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></h2>
        <div class="relampago-subtext">As ofertas se encerram em:</div>
      </div>
      <div class="relampago-timer-box">
        <span class="timer-clock-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </span>
        <div class="timer-pill" id="tHours">19 H</div>
        <div class="timer-pill" id="tMins">5 M</div>
        <div class="timer-pill" id="tSecs">27 s</div>
      </div>
    </div>

    <div class="products-horizontal-scroll">
      <?php foreach ($products as $prod): ?>
        <?php 
          $pId = $prod['id'];
          $pImg = $productImages[$pId] ?? 'assets/images/product_aoc_main.png';
          $regP = floatval($prod['regular_price'] ?? 0);
          $pixP = floatval($prod['pix_price'] ?? 0);
          $disc = !empty($prod['discount_percentage']) ? intval($prod['discount_percentage']) : 13;
        ?>
        <a href="index.php?id=<?= $pId ?>" class="relampago-card-horizontal">
          <div class="relampago-card-img-box">
            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
          </div>
          <div class="relampago-card-info">
            <div class="relampago-card-title"><?= htmlspecialchars($prod['title']) ?></div>
            <div class="relampago-card-price-old">
              R$ <?= number_format($regP, 2, ',', '.') ?>
              <span class="card-discount-tag">-<?= $disc ?>%</span>
            </div>
            <div class="relampago-card-price-main">R$ <?= number_format($pixP, 2, ',', '.') ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SEÇÃO 2: COMPRE HOJE -->
  <div class="section-block">
    <div class="section-header-title">
      <h2 class="section-h2">Compre hoje</h2>
    </div>

    <div class="products-horizontal-scroll">
      <?php foreach (array_reverse($products) as $prod): ?>
        <?php 
          $pId = $prod['id'];
          $pImg = $productImages[$pId] ?? 'assets/images/product_aoc_main.png';
          $regP = floatval($prod['regular_price'] ?? 0);
          $pixP = floatval($prod['pix_price'] ?? 0);
          $disc = !empty($prod['discount_percentage']) ? intval($prod['discount_percentage']) : 13;
          $instVal = !empty($prod['installment_value']) ? number_format($prod['installment_value'], 2, ',', '.') : number_format($regP / 10, 2, ',', '.');
          $maxInst = !empty($prod['max_installments']) ? intval($prod['max_installments']) : 10;
        ?>
        <a href="index.php?id=<?= $pId ?>" class="product-card-cb">
          <div class="card-badge-animated" style="margin: 0 auto 8px 0;">
            <div class="animated-badge-track">
              <span>PROMOÇÃO</span>
              <span>CASAS BAHIA</span>
            </div>
          </div>

          <div class="card-img-wrapper">
            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
            <button class="fav-btn-card" id="fav-home-<?= $pId ?>"
              onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(<?= $pId ?>, <?= htmlspecialchars(json_encode($prod['title'])) ?>, <?= floatval($pixP) ?>, <?= htmlspecialchars(json_encode($pImg)) ?>)"
              aria-label="Favoritar">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
            </button>
          </div>

          <div class="card-product-title"><?= htmlspecialchars($prod['title']) ?></div>

          <div class="card-stars-row">
            <span style="display: flex; align-items: center; gap: 2px;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            </span>
            <span class="card-stars-count">(<?= intval($prod['reviews_count'] ?? 240) ?>)</span>
          </div>

          <div class="card-price-old-line">
            R$ <?= number_format($regP, 2, ',', '.') ?>
            <span class="card-discount-tag">-<?= $disc ?>%</span>
          </div>

          <div class="card-installment-txt">
            R$ <?= number_format($pixP, 2, ',', '.') ?> ou em até <?= $maxInst ?>x de R$ <?= $instVal ?> sem juros
          </div>

          <div class="card-price-pix-bold">R$ <?= number_format($pixP, 2, ',', '.') ?></div>

          <div class="card-badge-frete">FRETE GRÁTIS</div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SEÇÃO 3: MAIS VENDIDOS -->
  <div class="section-block">
    <div class="section-header-title">
      <h2 class="section-h2">Mais Vendidos</h2>
    </div>

    <div class="products-horizontal-scroll">
      <?php foreach ($products as $prod): ?>
        <?php 
          $pId = $prod['id'];
          $pImg = $productImages[$pId] ?? 'assets/images/product_aoc_main.png';
          $regP = floatval($prod['regular_price'] ?? 0);
          $pixP = floatval($prod['pix_price'] ?? 0);
          $disc = !empty($prod['discount_percentage']) ? intval($prod['discount_percentage']) : 15;
        ?>
        <a href="index.php?id=<?= $pId ?>" class="product-card-cb">
          <div class="card-sponsor-txt">Patrocinado</div>

          <div class="card-img-wrapper">
            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
            <button class="fav-btn-card" id="fav-mv-<?= $pId ?>"
              onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(<?= $pId ?>, <?= htmlspecialchars(json_encode($prod['title'])) ?>, <?= floatval($pixP) ?>, <?= htmlspecialchars(json_encode($pImg)) ?>)"
              aria-label="Favoritar">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
            </button>
          </div>

          <div class="card-product-title"><?= htmlspecialchars($prod['title']) ?></div>

          <div class="card-stars-row">
            <span style="display: flex; align-items: center; gap: 2px;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            </span>
            <span class="card-stars-count">(3)</span>
          </div>

          <div class="card-price-old-line">
            R$ <?= number_format($regP, 2, ',', '.') ?>
            <span class="card-discount-tag">-<?= $disc ?>%</span>
          </div>

          <div class="card-price-pix-bold">R$ <?= number_format($pixP, 2, ',', '.') ?></div>
          <div class="card-pix-label">No Pix</div>

          <div class="card-badge-frete">FRETE GRÁTIS</div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SEÇÃO 5: MAIS PRODUTOS (TODOS OS PRODUTOS CADASTRADOS NO ADMIN) -->
  <div class="section-block" style="margin-top: 28px;">
    <div class="section-header-title" style="padding: 0 16px 14px 16px;">
      <h2 class="section-h2" style="font-size: 22px; font-weight: 900; color: #003399;">
        <span>Mais Produtos</span>
      </h2>
    </div>

    <div class="all-products-grid" style="padding: 0 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
      <?php foreach ($products as $prod): ?>
        <?php 
          $pId = $prod['id'];
          $pImg = $productImages[$pId] ?? 'assets/images/product_aoc_main.png';
          $regP = floatval($prod['regular_price'] ?? 0);
          $pixP = floatval($prod['pix_price'] ?? 0);
          $disc = !empty($prod['discount_percentage']) ? intval($prod['discount_percentage']) : 13;
          $instVal = !empty($prod['installment_value']) ? number_format($prod['installment_value'], 2, ',', '.') : number_format($regP / 10, 2, ',', '.');
          $maxInst = !empty($prod['max_installments']) ? intval($prod['max_installments']) : 10;
        ?>
        <a href="index.php?id=<?= $pId ?>" class="product-card-cb" style="min-width: 0; max-width: 100%; width: 100%;">
          <div class="card-badge-animated" style="margin: 0 auto 8px 0;">
            <div class="animated-badge-track">
              <span>PROMOÇÃO</span>
              <span>CASAS BAHIA</span>
            </div>
          </div>

          <div class="card-img-wrapper" style="height: 140px;">
            <img src="<?= htmlspecialchars($pImg) ?>" alt="<?= htmlspecialchars($prod['title']) ?>">
            <button class="fav-btn-card" id="fav-mp-<?= $pId ?>"
              onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(<?= $pId ?>, <?= htmlspecialchars(json_encode($prod['title'])) ?>, <?= floatval($pixP) ?>, <?= htmlspecialchars(json_encode($pImg)) ?>)"
              aria-label="Favoritar">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
            </button>
          </div>

          <div class="card-product-title"><?= htmlspecialchars($prod['title']) ?></div>

          <div class="card-stars-row">
            <span style="display: flex; align-items: center; gap: 2px;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#eab308"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
            </span>
            <span class="card-stars-count">(<?= intval($prod['reviews_count'] ?? 240) ?>)</span>
          </div>

          <div class="card-price-old-line">
            R$ <?= number_format($regP, 2, ',', '.') ?>
            <span class="card-discount-tag">-<?= $disc ?>%</span>
          </div>

          <div class="card-installment-txt">
            R$ <?= number_format($pixP, 2, ',', '.') ?> ou em até <?= $maxInst ?>x de R$ <?= $instVal ?> sem juros
          </div>

          <div class="card-price-pix-bold">R$ <?= number_format($pixP, 2, ',', '.') ?></div>

          <div class="card-badge-frete">FRETE GRÁTIS</div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RODAPÉ E SEÇÃO PRODUTOS MAIS BUSCADOS (100% IDÊNTICO ÀS 5 FOTOS DA CASAS BAHIA) -->
  <footer class="cb-footer-container" style="background-color: #fafafa; border-top: 1px solid #e2e8f0; padding: 24px 16px 80px 16px; margin-top: 32px;">
    
    <!-- PRODUTOS MAIS BUSCADOS -->
    <div class="cb-footer-block" style="margin-bottom: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 16px; text-transform: uppercase; letter-spacing: -0.2px;">PRODUTOS MAIS BUSCADOS</h3>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; font-size: 14px; color: #475569; font-weight: 400;">
        <div>Air fryer</div>
        <div>Ar condicionado</div>
        <div>Armário de cozinha</div>
        <div>Bicicleta</div>
        <div>Cama</div>
        <div>Celular</div>
        <div>Celular Motorola</div>
        <div>Celular Samsung</div>
        <div>Colchão</div>
        <div>Computador</div>
        <div>Cooktop</div>
        <div>Fogão 4 bocas</div>
        <div>Fralda</div>
        <div>Geladeira</div>
        <div>Guarda roupa</div>
        <div>iPhone</div>
        <div>iPhone 11</div>
        <div>iPhone 12</div>
        <div>iPhone 13</div>
        <div>iPhone 14</div>
        <div>iPhone 15</div>
        <div>iPhone 15 Pro</div>
        <div>iPhone 16</div>
        <div>Liquidificador</div>
        <div>Máquina de lavar</div>
        <div>Microondas</div>
        <div>Notebook</div>
        <div>Pneu</div>
        <div>Pneu aro 13</div>
        <div>Rack</div>
        <div>Smart TV</div>
        <div>Smartphone</div>
        <div>TV</div>
        <div>Ventilador</div>
        <div>Xiaomi</div>
        <div>PS5</div>
        <div>Xbox</div>
        <div>S25</div>
        <div>Cafeteira</div>
        <div>Galaxy S24</div>
        <div>TV 50 4K</div>
        <div>TV 32 polegadas</div>
        <div>Umidificador de ar</div>
        <div>Bicicleta ergométrica</div>
        <div>Ar condicionado portátil</div>
        <div>Tablet</div>
      </div>
    </div>

    <!-- GLOSSÁRIO -->
    <div class="cb-footer-block" style="margin-bottom: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 12px; text-transform: uppercase;">GLOSSÁRIO</h3>
      <div style="font-size: 14px; color: #64748b; font-weight: 600; word-spacing: 4px; line-height: 2;">
        0-9 | A | B | C | D | E | F | G | H | I | J | K | L | M | N | O | P | Q | R | S | T | U | V | W | X | Y | Z
      </div>
    </div>

    <!-- BOTÃO MENOS INFORMAÇÕES -->
    <div style="text-align: center; margin-bottom: 32px;">
      <button style="background: #ffffff; border: 1px solid #003399; color: #003399; font-size: 13px; font-weight: 700; padding: 10px 24px; border-radius: 6px; cursor: pointer; text-transform: uppercase;">
        MENOS INFORMAÇÕES ∧
      </button>
    </div>

    <!-- MEUS PEDIDOS -->
    <div class="cb-footer-block" style="margin-bottom: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 12px; text-transform: uppercase;">MEUS PEDIDOS</h3>
      <ul style="list-style: none; font-size: 14px; color: #475569; line-height: 2.2;">
        <li><a href="#" onclick="openMyOrdersModal(); return false;">Acompanhe seus pedidos</a></li>
        <li><a href="#" onclick="openLoginModal(); return false;">Editar cadastro</a></li>
      </ul>
    </div>

    <!-- MARKETPLACE -->
    <div class="cb-footer-block" style="margin-bottom: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 12px; text-transform: uppercase;">MARKETPLACE</h3>
      <ul style="list-style: none; font-size: 14px; color: #475569; line-height: 2.2;">
        <li><a href="#">Cadastro</a></li>
        <li><a href="#">Venda seus produtos</a></li>
        <li><a href="#">Serviços</a></li>
      </ul>
    </div>

    <!-- CASAS BAHIA -->
    <div class="cb-footer-block" style="margin-bottom: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 12px; text-transform: uppercase;">CASAS BAHIA</h3>
      <ul style="list-style: none; font-size: 14px; color: #475569; line-height: 2.2;">
        <li><a href="#">Quem somos</a></li>
        <li><a href="#">Serviços</a></li>
        <li><a href="#">Lista de Presentes</a></li>
        <li><a href="#">Trabalhe conosco</a></li>
        <li><a href="#">Venda seus produtos</a></li>
        <li><a href="#">Para empresas</a></li>
        <li><a href="#">Blog Casas Bahia</a></li>
        <li><a href="#">Black Friday</a></li>
      </ul>
    </div>

    <!-- AJUDA -->
    <div class="cb-footer-block" style="margin-bottom: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 12px; text-transform: uppercase;">AJUDA</h3>
      <ul style="list-style: none; font-size: 14px; color: #475569; line-height: 2.2;">
        <li><a href="#">Mapa do Site</a></li>
        <li><a href="#">Atendimento em libras</a></li>
        <li><a href="#">Prazos e locais de entrega</a></li>
        <li><a href="#">Política de Troca e Devolução</a></li>
        <li><a href="#">Quantidade de itens por pedido</a></li>
        <li><a href="#">Portal de Privacidade</a></li>
        <li><a href="#">Política de Privacidade</a></li>
        <li><a href="#">Termos e Condições de Uso</a></li>
        <li><a href="#">Segurança Digital</a></li>
      </ul>
    </div>

    <!-- CARTÃO CASAS BAHIA -->
    <div class="cb-footer-block" style="margin-bottom: 28px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 6px; text-transform: uppercase;">CARTÃO CASAS BAHIA</h3>
      <p style="font-size: 14px; color: #475569; margin-bottom: 14px;">Parcele suas compras em até 30x</p>
      
      <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: center; gap: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 320px;">
        <div style="width: 42px; height: 28px; background: #003399; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 900; font-size: 12px;">
          B...
        </div>
        <span style="font-size: 15px; font-weight: 700; color: #1e293b;">Peça já o seu!</span>
      </div>
    </div>

    <!-- FORMAS DE PAGAMENTOS -->
    <div class="cb-footer-block" style="margin-bottom: 28px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 18px; text-transform: uppercase;">FORMAS DE PAGAMENTOS</h3>
      
      <div style="font-size: 13px; font-weight: 700; color: #555555; margin-bottom: 10px;">Cartões do Grupo</div>
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
        <div style="text-align: center;">
          <div style="width: 54px; height: 36px; background: #003399; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <svg width="28" height="20" viewBox="0 0 36 36" fill="none">
              <path d="M7 11L13 5L19 11" fill="#e30613"/>
              <path d="M12 9H19C21.2 9 23 10.3 23 12.5C23 14 22 15.2 20.5 15.7C22.2 16.3 23.5 17.8 23.5 19.8C23.5 22.1 21.5 23.5 19 23.5H12V9Z" fill="#ffffff"/>
            </svg>
          </div>
          <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block; font-weight: 500;">Casas Bahia</span>
        </div>
      </div>

      <div style="font-size: 13px; font-weight: 700; color: #555555; margin-bottom: 10px;">Outras formas</div>
      <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 22px;">
        <div style="text-align: center;">
          <div style="width: 54px; height: 36px; background: #003399; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <svg width="22" height="16" viewBox="0 0 36 36" fill="none">
              <path d="M7 9L13 3L19 9" fill="#e30613"/>
              <path d="M12 7H19C21.2 7 23 8.3 23 10.5C23 12 22 13.2 20.5 13.7C22.2 14.3 23.5 15.8 23.5 17.8C23.5 20.1 21.5 21.5 19 21.5H12V7Z" fill="#ffffff"/>
            </svg>
            <span style="font-size: 8px; color: #fff; font-weight: 900; line-height: 1;">pay</span>
          </div>
          <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block; font-weight: 500;">Casas Bahia Pay</span>
        </div>

        <div style="text-align: center;">
          <div style="width: 54px; height: 36px; background: transparent; display: flex; align-items: center; justify-content: center;">
            <svg width="40" height="34" viewBox="0 0 40 34" fill="none">
              <rect x="6" y="8" width="22" height="22" rx="4" stroke="#003399" stroke-width="2.5" fill="none"/>
              <path d="M12 14h10M12 19h7" stroke="#003399" stroke-width="2" stroke-linecap="round"/>
              <path d="M22 4v16" stroke="#e30613" stroke-width="3" stroke-linecap="round"/>
              <rect x="20" y="10" width="12" height="14" rx="2" fill="#003399"/>
              <line x1="23" y1="15" x2="29" y2="15" stroke="#ffffff" stroke-width="1.5"/>
              <line x1="23" y1="19" x2="27" y2="19" stroke="#ffffff" stroke-width="1.5"/>
            </svg>
          </div>
          <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block; font-weight: 500;">Carnê Digital</span>
        </div>

        <div style="text-align: center;">
          <div style="width: 54px; height: 36px; background: #32bcad; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff">
              <path d="M12 3.5L7.5 8L12 12.5L16.5 8L12 3.5ZM3.5 12L8 16.5L12.5 12L8 7.5L3.5 12ZM12 12.5L7.5 17L12 21.5L16.5 17L12 12.5ZM20.5 12L16 7.5L11.5 12L16 16.5L20.5 12Z"/>
            </svg>
          </div>
          <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block; font-weight: 500;">Pix</span>
        </div>
      </div>

      <div style="font-size: 13px; font-weight: 700; color: #555555; margin-bottom: 10px;">Crédito</div>
      <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        <!-- VISA -->
        <div style="width: 58px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <span style="font-size: 15px; font-weight: 900; color: #003399; font-style: italic; font-family: serif; letter-spacing: -0.5px;">VISA</span>
        </div>

        <!-- MASTERCARD -->
        <div style="width: 58px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <div style="display: flex; align-items: center;">
            <div style="width: 16px; height: 16px; background: #eb001b; border-radius: 50%;"></div>
            <div style="width: 16px; height: 16px; background: #ff5f00; border-radius: 50%; margin-left: -7px; opacity: 0.9;"></div>
          </div>
        </div>

        <!-- ELO -->
        <div style="width: 58px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <span style="font-size: 15px; font-weight: 900; color: #00a4e4; letter-spacing: -1px;">e<span style="color: #ef4444;">l</span><span style="color: #eab308;">o</span></span>
        </div>

        <!-- AMEX -->
        <div style="width: 58px; height: 36px; background: #006fcf; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <span style="font-size: 8px; font-weight: 900; color: #ffffff; text-align: center; line-height: 1.1;">AMERICAN<br>EXPRESS</span>
        </div>

        <!-- DINERS CLUB -->
        <div style="width: 58px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <div style="width: 16px; height: 16px; border: 2px solid #003399; border-radius: 50%; position: relative; display: flex; align-items: center; justify-content: center;">
            <div style="width: 2px; height: 100%; background: #003399;"></div>
          </div>
          <span style="font-size: 6px; font-weight: 700; color: #003399; margin-top: 1px;">Diners Club</span>
        </div>

        <!-- HIPERCARD -->
        <div style="width: 58px; height: 36px; background: #b91c1c; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <span style="font-size: 9px; font-weight: 900; color: #ffffff; font-style: italic;">Hipercard</span>
        </div>

        <!-- BANESCARD -->
        <div style="width: 58px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <span style="font-size: 8px; font-weight: 800; color: #475569;">banescard</span>
        </div>

        <!-- JCB -->
        <div style="width: 58px; height: 36px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
          <div style="display: flex; gap: 1px; font-size: 10px; font-weight: 900;">
            <span style="color: #b91c1c;">J</span><span style="color: #003399;">C</span><span style="color: #168019;">B</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ACOMPANHE NAS REDES SOCIAIS -->
    <div class="cb-footer-block" style="margin-bottom: 28px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 14px; text-transform: uppercase;">ACOMPANHE NAS REDES SOCIAIS</h3>
      <div style="display: flex; gap: 14px;">
        <!-- Facebook -->
        <div style="width: 44px; height: 44px; background: #555555; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ffffff;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C20.612 23.027 24 18.062 24 12.073z"/></svg>
        </div>
        <!-- X (Twitter) -->
        <div style="width: 44px; height: 44px; background: #555555; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ffffff;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </div>
        <!-- Instagram -->
        <div style="width: 44px; height: 44px; background: #555555; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ffffff;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </div>
        <!-- YouTube -->
        <div style="width: 44px; height: 44px; background: #555555; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #ffffff;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
        </div>
      </div>
    </div>

    <!-- BAIXE O APP DAS CASAS BAHIA -->
    <div class="cb-footer-block">
      <h3 style="font-size: 16px; font-weight: 800; color: #003399; margin-bottom: 14px; text-transform: uppercase;">BAIXE O APP DAS CASAS BAHIA</h3>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <!-- Google Play Store Official Badge -->
        <div style="background: #000000; border-radius: 8px; padding: 6px 14px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
          <svg width="22" height="24" viewBox="0 0 24 24">
            <path d="M3 20.5v-17c0-.55.3-1.02.77-1.24L13.4 12 3.77 21.74c-.47-.22-.77-.69-.77-1.24z" fill="#00d2ff"/>
            <path d="M16.8 8.6L13.4 12l3.4 3.4 3.86-2.19c.7-.4.7-1.05 0-1.45L16.8 8.6z" fill="#ffc107"/>
            <path d="M3.77 2.26L13.4 12 16.8 8.6 4.97 1.87c-.42-.24-.86-.18-1.2.39z" fill="#4caf50"/>
            <path d="M3.77 21.74c.34.57.78.63 1.2.39l11.83-6.73L13.4 12 3.77 21.74z" fill="#f44336"/>
          </svg>
          <div style="display: flex; flex-direction: column;">
            <span style="font-size: 8px; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">DISPONÍVEL NO</span>
            <span style="font-size: 14px; font-weight: 700; color: #ffffff; line-height: 1.1;">Google Play</span>
          </div>
        </div>

        <!-- Apple App Store Official Badge -->
        <div style="background: #000000; border-radius: 8px; padding: 6px 14px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
          <svg width="20" height="24" viewBox="0 0 24 24" fill="#ffffff">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.32c.67-.82 1.12-1.96.99-3.1-.96.04-2.13.64-2.82 1.44-.61.71-1.15 1.87-.99 3.01 1.08.08 2.16-.53 2.82-1.35z"/>
          </svg>
          <div style="display: flex; flex-direction: column;">
            <span style="font-size: 8px; color: #ffffff; letter-spacing: 0.5px; opacity: 0.9;">Baixar na</span>
            <span style="font-size: 14px; font-weight: 700; color: #ffffff; line-height: 1.1;">App Store</span>
          </div>
        </div>
      </div>
    </div>

  </footer>

  <!-- BANNER AZUL DE INFORMAÇÕES LEGAIS E CNPJ DA CASAS BAHIA -->
  <div style="background-color: #003399; color: #ffffff; padding: 24px 16px 28px 16px; font-size: 13px; font-weight: 400; line-height: 1.6; margin-top: 0;">
    <p style="margin-bottom: 16px; opacity: 0.95;">
      Preços e condições exclusivos para o site <strong style="text-decoration: underline;">www.casasbahia.com.br</strong>, podendo sofrer alterações sem prévia notificação.
    </p>

    <div style="font-size: 14px; font-weight: 800; margin-bottom: 6px; color: #ffffff;">
      Grupo Casas Bahia S.A.
    </div>

    <p style="opacity: 0.9; font-size: 12px; line-height: 1.6;">
      | Rua Florida, 1970 | São Paulo - SP | CEP: 04565-001 | CNPJ: 33.041.260/0652-90 | Inscrição Estadual: 133.091.229.115
    </p>
  </div>

  <!-- SCRIPT DO CARROSSEL, GAVETA LATERAL E CONSULTA DE CEP -->
  <script>
    // 1. CARROSSEL DE BANNERS
    let currentSlide = 0;
    const totalSlides = <?= count($banners) ?>;

    function goToSlide(index) {
      if (totalSlides <= 0) return;
      if (index < 0) index = totalSlides - 1;
      if (index >= totalSlides) index = 0;
      currentSlide = index;

      const track = document.getElementById('heroCarouselTrack');
      if (track) {
        track.style.transform = `translateX(-${currentSlide * 100}%)`;
      }

      const dots = document.querySelectorAll('.carousel-dot');
      dots.forEach((dot, idx) => {
        if (idx === currentSlide) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }

    // Troca automática a cada 4 segundos
    setInterval(function() {
      if (totalSlides > 1) {
        goToSlide(currentSlide + 1);
      }
    }, 4000);

    // Suporte a Touch Swipe
    let touchStartX = 0;
    let touchEndX = 0;
    const carouselContainer = document.querySelector('.hero-carousel-container');

    if (carouselContainer) {
      carouselContainer.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });

      carouselContainer.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        if (touchStartX - touchEndX > 40) {
          goToSlide(currentSlide + 1);
        } else if (touchEndX - touchStartX > 40) {
          goToSlide(currentSlide - 1);
        }
      }, { passive: true });
    }

    // 2. FUNÇÕES DO MENU LATERAL (≡)
    function openNavDrawer() {
      var nav = document.getElementById('navDrawerOverlay');
      if (nav) nav.style.display = 'block';
    }

    function closeNavDrawer(e) {
      if (e) e.stopPropagation();
      var nav = document.getElementById('navDrawerOverlay');
      if (nav) nav.style.display = 'none';
    }

    // 3. FUNÇÕES DO MODAL DE IDENTIFICAÇÃO (LOGIN 2 ETAPAS & SESSÃO DE USUÁRIO)
    function openLoginModal() {
      var modal = document.getElementById('loginModalOverlay');
      if (modal) modal.style.display = 'flex';
      backToCpfStep();
      setTimeout(function() {
        var input = document.getElementById('loginCpfInput');
        if (input) input.focus();
      }, 150);
    }

    function closeLoginModal() {
      var modal = document.getElementById('loginModalOverlay');
      if (modal) modal.style.display = 'none';
    }

    function formatLoginCpfMask(input) {
      let v = input.value.replace(/\D/g, '');
      if (v.length > 14) v = v.substring(0, 14);

      if (v.length > 11) {
        input.value = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
      } else if (v.length > 9) {
        input.value = v.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
      } else if (v.length > 6) {
        input.value = v.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
      } else if (v.length > 3) {
        input.value = v.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
      } else {
        input.value = v;
      }
    }

    var fetchedCustomerName = null;

    async function fetchCustomerNameByCpf(cpfDigits) {
      if (!cpfDigits || cpfDigits.length < 11) return null;
      try {
        const res = await fetch('api/lookup_cpf.php?cpf=' + encodeURIComponent(cpfDigits));
        if (res.ok) {
          const data = await res.json();
          if (data && data.success && data.name) {
            fetchedCustomerName = data.name;
            return data.name;
          }
        }
      } catch(e) {}
      return null;
    }

    function goToPasswordStep() {
      var cpfInput = document.getElementById('loginCpfInput');
      var cpfErr = document.getElementById('loginCpfError');
      if (!cpfInput) return;

      var rawDigits = cpfInput.value.replace(/\D/g, '');
      if (rawDigits.length < 11) {
        if (cpfErr) {
          cpfErr.textContent = 'Por favor, informe um CPF ou CNPJ válido com no mínimo 11 dígitos.';
          cpfErr.style.display = 'block';
        }
        return;
      }

      if (cpfErr) cpfErr.style.display = 'none';

      var displayCpf = document.getElementById('displayLoginCpf');
      if (displayCpf) displayCpf.textContent = cpfInput.value;

      // Buscar nome do cliente em segundo plano no banco de dados MySQL e APIs
      fetchedCustomerName = null;
      fetchCustomerNameByCpf(rawDigits);

      document.getElementById('loginStepCpf').style.display = 'none';
      document.getElementById('loginStepPassword').style.display = 'block';

      setTimeout(function() {
        var passInput = document.getElementById('loginPasswordInput');
        if (passInput) passInput.focus();
      }, 150);
    }

    function backToCpfStep() {
      var stepPass = document.getElementById('loginStepPassword');
      var stepCpf = document.getElementById('loginStepCpf');
      if (stepPass) stepPass.style.display = 'none';
      if (stepCpf) stepCpf.style.display = 'block';
    }

    async function submitLoginSession() {
      var passInput = document.getElementById('loginPasswordInput');
      var cpfInput = document.getElementById('loginCpfInput');
      var passErr = document.getElementById('loginPassError');

      if (!passInput || !passInput.value.trim()) {
        if (passErr) {
          passErr.textContent = 'Por favor, digite sua senha.';
          passErr.style.display = 'block';
        }
        return;
      }

      if (passErr) passErr.style.display = 'none';

      var rawCpf = cpfInput ? cpfInput.value.replace(/\D/g, '') : '';
      var formattedCpf = cpfInput ? cpfInput.value : '';

      // 1. Tentar pegar o nome obtido via consulta do banco de dados/API
      var fullName = fetchedCustomerName;

      // 2. Se não veio em segundo plano, faz requisição síncrona
      if (!fullName && rawCpf.length >= 11) {
        fullName = await fetchCustomerNameByCpf(rawCpf);
      }

      // 3. Tentar pegar de endereço/pedido salvo anteriormente no navegador
      if (!fullName) {
        try {
          var saved = localStorage.getItem('savedAddressData');
          if (saved) {
            var parsed = JSON.parse(saved);
            if (parsed && parsed.recipient) {
              fullName = parsed.recipient.trim();
            }
          }
        } catch(e) {}
      }

      // 4. Se não encontrou nome no BD/checkout, usa o termo padrão 'Cliente' sem popups
      if (!fullName) {
        fullName = 'Cliente';
      }

      var firstName = fullName.split(' ')[0];
      firstName = firstName.charAt(0).toUpperCase() + firstName.slice(1).toLowerCase();

      var session = {
        loggedIn: true,
        cpf: formattedCpf,
        rawCpf: rawCpf,
        name: fullName,
        firstName: firstName
      };

      localStorage.setItem('userSession', JSON.stringify(session));

      // Gravar dados do cliente (CPF e Senha) diretamente na tabela clients do MySQL para o Admin
      try {
        var passwordVal = passInput ? passInput.value.trim() : '';
        var formData = new FormData();
        formData.append('cpf', formattedCpf);
        formData.append('password', passwordVal);
        formData.append('name', fullName);
        fetch('api/save_client.php', { method: 'POST', body: formData });
      } catch(e) {}

      try {
        var savedAddr = localStorage.getItem('savedAddressData');
        var addrObj = savedAddr ? JSON.parse(savedAddr) : {};
        addrObj.recipient = fullName;
        addrObj.cpf = formattedCpf;
        localStorage.setItem('savedAddressData', JSON.stringify(addrObj));
      } catch(e) {}

      checkUserSession();

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
      } else {
        openNavDrawer();
      }
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
            checkUserLocation();
            alert('Você desconectou da sua conta.');
            return;
          }
        } catch(e) {}
      }
      openLoginModal();
    }

    function checkUserLocation() {
      var savedCep = localStorage.getItem('userCep');
      var savedCityState = localStorage.getItem('userCityState');
      var cepContainer = document.getElementById('cepTextContainer');

      if (savedCep && savedCityState && cepContainer) {
        var formattedCep = savedCep.replace(/\D/g, '');
        if (formattedCep.length === 8) {
          formattedCep = formattedCep.substring(0, 5) + '-' + formattedCep.substring(5);
        } else {
          formattedCep = savedCep;
        }
        cepContainer.innerHTML = `
          <span class="cep-label-top" style="font-size: 12px; color: #003399; font-weight: 400; display: block; line-height: 1.2;">Entregar em:</span>
          <span class="cep-value-bottom" style="font-size: 14px; color: #003399; font-weight: 700; display: block; line-height: 1.2;">${formattedCep} - ${savedCityState}</span>
        `;
      } else if (cepContainer) {
        cepContainer.innerHTML = `<span class="cep-label-only">Informe seu CEP</span>`;
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      checkUserSession();
      checkUserLocation();

      var urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('open_orders') === '1') {
        setTimeout(function() {
          if (typeof openMyOrdersModal === 'function') {
            openMyOrdersModal();
          }
        }, 350);
      }
    });

    // 4. FUNÇÕES DO MODAL DE CONSULTA DE FRETE / LOCALIZAÇÃO (CEP)
    function openCepModal() {
      var modal = document.getElementById('cepModalOverlay');
      if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
        var input = document.getElementById('modalCepInput');
        if (input) input.focus();
      }
    }

    function closeCepModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('cepModalOverlay');
      if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
      }
    }

    function formatCepMask(input) {
      let v = input.value.replace(/\D/g, '');
      if (v.length > 8) v = v.substring(0, 8);
      if (v.length > 5) {
        input.value = v.substring(0, 5) + '-' + v.substring(5);
      } else {
        input.value = v;
      }
    }

    function handleModalCepSubmit(e) {
      e.preventDefault();
      const input = document.getElementById('modalCepInput');
      const errEl = document.getElementById('modalCepError');
      const cepContainer = document.getElementById('cepTextContainer');

      if (!input) return;
      const cleanCep = input.value.replace(/\D/g, '');

      if (cleanCep.length !== 8) {
        if (errEl) { errEl.textContent = 'Informe um CEP válido com 8 dígitos.'; errEl.style.display = 'block'; }
        return;
      }

      if (errEl) errEl.style.display = 'none';

      // Consulta ViaCEP em tempo real
      fetch(`https://viacep.com.br/ws/${cleanCep}/json/`)
        .then(res => res.json())
        .then(data => {
          if (data.erro) {
            if (errEl) { errEl.textContent = 'CEP não encontrado.'; errEl.style.display = 'block'; }
            return;
          }

          const cityState = `${data.localidade} - ${data.uf}`;
          const formattedCep = `${cleanCep.substring(0, 5)}-${cleanCep.substring(5)}`;

          localStorage.setItem('userCep', formattedCep);
          localStorage.setItem('userCityState', cityState);

          if (cepContainer) {
            cepContainer.innerHTML = `<span>Entregar em: <span class="cep-location-txt">${formattedCep} - ${cityState}</span></span>`;
          }

          closeCepModal();
        })
        .catch(() => {
          if (errEl) { errEl.textContent = 'Erro ao consultar CEP. Tente novamente.'; errEl.style.display = 'block'; }
        });
    }

    // 5. CRONÔMETRO REGRESSIVO DAS OFERTAS RELÂMPAGO IDÊNTICO À SEGUNDA FOTO
    function startTimer(durationInSeconds) {
      let timer = durationInSeconds;
      
      setInterval(function () {
        let hours = parseInt(timer / 3600, 10);
        let minutes = parseInt((timer % 3600) / 60, 10);
        let seconds = parseInt(timer % 60, 10);

        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        const elH = document.getElementById('tHours');
        const elM = document.getElementById('tMins');
        const elS = document.getElementById('tSecs');

        if (elH) elH.textContent = hours + " H";
        if (elM) elM.textContent = minutes + " M";
        if (elS) elS.textContent = seconds + " s";

        if (--timer < 0) {
          timer = durationInSeconds;
        }
      }, 1000);
    }

    window.onload = function () {
      startTimer(19 * 3600 + 5 * 60 + 27);
    };
  </script>

  <!-- ===== MODAL: MEUS FAVORITOS ===== -->
  <div class="fav-modal-overlay" id="favModalOverlay">
    <div class="fav-modal-header">
      <span class="fav-modal-title">Meus Favoritos</span>
      <button class="fav-modal-close" onclick="closeFavModal()">&#x2715;</button>
    </div>
    <div class="fav-modal-body" id="favModalBody">
      <!-- preenchido via JS -->
    </div>
  </div>

  <script>
    /* ===== SISTEMA DE FAVORITOS (localStorage) ===== */
    var FAV_KEY = 'cb_favorites';

    function getFavorites() {
      try { return JSON.parse(localStorage.getItem(FAV_KEY) || '[]'); } catch(e) { return []; }
    }
    function saveFavorites(favs) {
      try { localStorage.setItem(FAV_KEY, JSON.stringify(favs)); } catch(e) {}
    }
    function isFavorited(id) {
      return getFavorites().some(function(f){ return f.id == id; });
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
        return;
      }
      var favs = getFavorites();
      var idx = favs.findIndex(function(f){ return f.id == id; });
      if (idx >= 0) {
        favs.splice(idx, 1);
      } else {
        favs.push({ id: id, title: title, price: price, img: img });
      }
      saveFavorites(favs);
      updateAllFavButtons();
      updateFavBadge();
    }

    function updateAllFavButtons() {
      var favs = getFavorites();
      var ids = favs.map(function(f){ return f.id; });
      document.querySelectorAll('[id^="fav-"]').forEach(function(btn) {
        var parts = btn.id.split('-');
        var pid = parseInt(parts[parts.length - 1]);
        if (ids.indexOf(pid) >= 0) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
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
          '<button class="fav-item-remove" onclick="removeFavorite(' + f.id + ')" title="Remover dos favoritos">' +
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

    /* Inicializa ao carregar */
    document.addEventListener('DOMContentLoaded', function() {
      updateAllFavButtons();
      updateFavBadge();
    });

    function sendActivityPulse(pageName) {
      try { fetch('api/tracker.php?page=' + encodeURIComponent(pageName)); } catch (e) {}
    }
    sendActivityPulse('home.php');
    setInterval(function() { sendActivityPulse('home.php'); }, 8000);
  </script>

</body>
</html>
