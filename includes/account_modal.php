<!-- MODAL MINHA CONTA (TELA INTEIRA - FOTO MEDIA_1787978552667.JPG E MEDIA_1787978560085.JPG) -->
<div class="my-account-modal-overlay" id="myAccountModalOverlay">
  <!-- TOPO LINHA VERMELHA -->
  <div style="height: 4px; background-color: #e30613; width: 100%;"></div>

  <!-- HEADER -->
  <header style="background: #ffffff; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100;">
    <a href="home.php" class="brand-logo" style="font-size: 20px; font-weight: 900; color: #003399; text-decoration: none; letter-spacing: -0.5px;">CASAS<span style="color: #e30613;">BAHIA</span></a>
    <div class="accessibility-icon" title="Acessibilidade" onclick="closeMyAccountModal()" style="cursor: pointer;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="1.8">
        <circle cx="12" cy="12" r="10"/>
        <circle cx="12" cy="6.5" r="1.5" fill="#003399" stroke="none"/>
        <path d="M7 10.5h10M12 10.5v5M9.5 19.5l2.5-4 2.5 4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </header>

  <!-- SUBHEADER VOLTAR -->
  <div style="background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
    <button type="button" onclick="closeMyAccountModal()" style="background: none; border: none; font-size: 14px; font-weight: 600; color: #64748b; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 0;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      Voltar
    </button>
  </div>

  <!-- CONTEÚDO PRINCIPAL -->
  <div style="max-width: 480px; margin: 0 auto; padding: 20px 16px;">
    
    <h1 style="font-size: 22px; font-weight: 700; color: #003399; margin-bottom: 4px;" id="accountUserGreeting">Bem vindo (a) Cliente</h1>
    <h2 style="font-size: 20px; font-weight: 700; color: #003399; margin-bottom: 20px;">Minha conta</h2>

    <!-- CARD 1: MEUS PEDIDOS -->
    <div class="my-account-card" onclick="closeMyAccountModal(); if (typeof openMyOrdersModal === 'function') openMyOrdersModal();">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Meus pedidos</div>
        <div class="my-account-card-sub">Detalhes, status e histórico.</div>
      </div>
    </div>

    <!-- CARD 2: MEUS PEDIDOS LOJAS FÍSICAS -->
    <div class="my-account-card" onclick="closeMyAccountModal(); if (typeof openMyOrdersModal === 'function') openMyOrdersModal();">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <circle cx="12" cy="12" r="3" fill="#003399"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Meus pedidos lojas físicas</div>
        <div class="my-account-card-sub">Acompanhar e detalhes do pedido.</div>
      </div>
    </div>

    <!-- CARD 3: ENDEREÇOS -->
    <div class="my-account-card" onclick="closeMyAccountModal(); window.location.href='endereco.php';">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Endereços</div>
        <div class="my-account-card-sub">Cadastre ou altere seu endereço.</div>
      </div>
    </div>

    <!-- CARD 4: DADOS CADASTRAIS -->
    <div class="my-account-card" onclick="alert('Seus dados cadastrais estão atualizados.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Dados cadastrais</div>
        <div class="my-account-card-sub">Altere e gerencie seus dados cadastrais.</div>
      </div>
    </div>

    <!-- CARD 5: CARNÊ DIGITAL -->
    <div class="my-account-card" onclick="alert('Carnê digital disponível na fase de pagamento.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="5" width="20" height="14" rx="2"/>
          <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Carnê digital</div>
        <div class="my-account-card-sub">Parcele suas compras.</div>
      </div>
    </div>

    <!-- CARD 6: CARTÃO CB -->
    <div class="my-account-card" onclick="alert('Solicite seu Cartão Casas Bahia.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="5" width="20" height="14" rx="2"/>
          <path d="M18 15h.01"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Cartão CB</div>
        <div class="my-account-card-sub">Adquira já seu cartão CB.</div>
      </div>
    </div>

    <!-- CARD 7: LISTA DE PRESENTES -->
    <div class="my-account-card" onclick="alert('Sua lista de presentes está vazia.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 12 20 22 4 22 4 12"/>
          <rect x="2" y="7" width="20" height="5"/>
          <line x1="12" y1="22" x2="12" y2="7"/>
          <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/>
          <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Lista de presentes</div>
        <div class="my-account-card-sub">Crie e consulte sua lista.</div>
      </div>
    </div>

    <!-- CARD 8: SEGURANÇA -->
    <div class="my-account-card" onclick="alert('Suas opções de segurança estão ativas.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Segurança</div>
        <div class="my-account-card-sub">Senha, dispositivos e biometria.</div>
      </div>
    </div>

    <!-- CARD 9: GARANTIAS E SEGUROS -->
    <div class="my-account-card" onclick="alert('Suas garantias e seguros estão em dia.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Garantias e seguros</div>
        <div class="my-account-card-sub">Confira seus produtos elegíveis para garantias estendidas.</div>
      </div>
    </div>

    <!-- CARD 10: ME CHAMA NO ZAP -->
    <div class="my-account-card" onclick="alert('Atendimento WhatsApp Casas Bahia.');">
      <div class="my-account-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#003399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
        </svg>
      </div>
      <div class="my-account-info">
        <div class="my-account-card-title">Me Chama no Zap</div>
        <div class="my-account-card-sub">Fale diretamente com o vendedor e seja orientado no seu processo de compra.</div>
      </div>
    </div>

    <!-- SEÇÃO ATENDIMENTO -->
    <h3 style="font-size: 18px; font-weight: 700; color: #003399; margin-top: 28px; margin-bottom: 8px;">Atendimento</h3>
    <div style="font-size: 14px; color: #64748b; line-height: 1.5;">
      Se você estiver com dúvidas sobre os processos dentro da plataforma clique em <a href="#" onclick="alert('Atendimento Casas Bahia.'); return false;" style="color: #003399; font-weight: 700; text-decoration: underline;">ir para atendimento</a>
    </div>

    <!-- BOTÃO SAIR DA CONTA -->
    <button type="button" class="btn-logout-account" onclick="handleAccountLogout()">
      Sair da conta
    </button>

  </div>
</div>

<style>
  .my-account-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #ffffff;
    z-index: 99999;
    overflow-y: auto;
    display: none;
  }
  .my-account-modal-overlay.active {
    display: block;
  }

  .my-account-card {
    background: #ffffff;
    border: 1.5px solid #003399;
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 14px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .my-account-card:active {
    transform: scale(0.98);
  }
  .my-account-icon {
    flex-shrink: 0;
    margin-top: 2px;
  }
  .my-account-info {
    flex: 1;
    min-width: 0;
  }
  .my-account-card-title {
    font-size: 18px;
    font-weight: 700;
    color: #003399;
    line-height: 1.25;
  }
  .my-account-card-sub {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
    line-height: 1.35;
  }

  .btn-logout-account {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 48px;
    background: #ffffff;
    border: 2px solid #e30613;
    color: #e30613;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 24px;
    margin-bottom: 20px;
    transition: background-color 0.2s;
  }
  .btn-logout-account:active {
    background-color: #fef2f2;
  }
</style>

<script>
  function openMyAccountModal() {
    var modal = document.getElementById('myAccountModalOverlay');
    if (!modal) return;
    
    var greetingElem = document.getElementById('accountUserGreeting');
    var firstName = 'Cliente';

    try {
      var sessionRaw = localStorage.getItem('userSession');
      if (sessionRaw) {
        var session = JSON.parse(sessionRaw);
        if (session && (session.firstName || session.name)) {
          firstName = session.firstName || session.name.split(' ')[0];
        }
      }
    } catch(e) {}

    if (firstName === 'Cliente') {
      try {
        var savedAddr = localStorage.getItem('savedAddressData');
        if (savedAddr) {
          var addr = JSON.parse(savedAddr);
          if (addr && addr.recipient) {
            firstName = addr.recipient.trim().split(' ')[0];
          }
        }
      } catch(e) {}
    }

    if (greetingElem) {
      if (firstName && firstName !== 'Cliente') {
        greetingElem.textContent = 'Bem vindo (a) ' + firstName;
      } else {
        greetingElem.textContent = 'Bem vindo (a) Cliente';
      }
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeMyAccountModal() {
    var modal = document.getElementById('myAccountModalOverlay');
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  function handleAccountLogout() {
    if (confirm('Deseja realmente sair da sua conta?')) {
      localStorage.removeItem('userSession');
      localStorage.removeItem('userCep');
      localStorage.removeItem('userCityState');
      localStorage.removeItem('savedAddressData');
      localStorage.removeItem('last_order_id');
      if (typeof checkUserSession === 'function') checkUserSession();
      closeMyAccountModal();
      alert('Você saiu da sua conta.');
    }
  }
</script>
