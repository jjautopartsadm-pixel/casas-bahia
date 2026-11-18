/**
 * Casas Bahia Mobile PDP Interatividade JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {

  // 0. Modal Expansível do Topo (Ofertas Exclusivas)
  const newsletterForm = document.getElementById('newsletterForm');
  const newsletterSuccess = document.getElementById('newsletterSuccess');

  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = document.getElementById('newsEmail').value;
      const phone = document.getElementById('newsPhone').value;

      if (!email || !phone) {
        alert('Por favor, informe seu e-mail e telefone.');
        return;
      }

      newsletterForm.style.display = 'none';
      if (newsletterSuccess) {
        newsletterSuccess.style.display = 'block';
      }
    });
  }

  // 0.01 Botão de Compartilhar do PDP
  const shareButtons = document.querySelectorAll('.circle-btn[aria-label="Compartilhar"], button[aria-label="Compartilhar"]');
  shareButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      if (typeof handleProductShare === 'function') {
        handleProductShare(e);
      } else if (typeof openShareModal === 'function') {
        openShareModal();
      }
    });
  });

  // 0.02 Botão de Favoritar do PDP (Coração)
  const favButtons = document.querySelectorAll('.circle-btn[aria-label="Favoritar"]');
  favButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const svg = btn.querySelector('svg path');
      if (svg) {
        const isFav = btn.classList.toggle('favorited');
        svg.setAttribute('fill', isFav ? '#e30613' : 'none');
        svg.setAttribute('stroke', isFav ? '#e30613' : '#003399');
      }
    });
  });

  // 0.03 Botão "Ver mais" das Características do Produto
  const specMoreLinks = document.querySelectorAll('.spec-more-link');
  specMoreLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      if (typeof openSpecsModal === 'function') {
        openSpecsModal(e);
      }
    });
  });

  // 0.1 Modal Escolha sua Localização (CEP)
  const cepBarToggle = document.getElementById('cepBarToggle');
  const cepModalOverlay = document.getElementById('cepModalOverlay');
  const cepModalClose = document.getElementById('cepModalClose');
  const modalCepForm = document.getElementById('modalCepForm');
  const modalCepInput = document.getElementById('modalCepInput');
  const modalCepError = document.getElementById('modalCepError');
  const cepTextContainer = document.getElementById('cepTextContainer');

  const pdpCepInput = document.getElementById('pdpCepInput');
  const pdpShippingResult = document.getElementById('pdpShippingResult');
  const pdpShippingCity = document.getElementById('pdpShippingCity');

  // Abrir Modal ao clicar na barra de CEP
  if (cepBarToggle && cepModalOverlay) {
    cepBarToggle.addEventListener('click', () => {
      cepModalOverlay.style.display = 'flex';
      cepModalOverlay.classList.add('active');
      if (modalCepInput) {
        modalCepInput.focus();
      }
    });
  }

  // Fechar Modal no X
  if (cepModalClose && cepModalOverlay) {
    cepModalClose.addEventListener('click', (e) => {
      e.stopPropagation();
      cepModalOverlay.style.display = 'none';
      cepModalOverlay.classList.remove('active');
    });
  }

  // Fechar Modal clicando no fundo escuro
  if (cepModalOverlay) {
    cepModalOverlay.addEventListener('click', (e) => {
      if (e.target === cepModalOverlay) {
        cepModalOverlay.style.display = 'none';
        cepModalOverlay.classList.remove('active');
      }
    });
  }

  // Máscara de CEP (00000-000)
  if (modalCepInput) {
    modalCepInput.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\D/g, '');
      if (v.length > 5) {
        v = v.substring(0, 5) + '-' + v.substring(5, 8);
      }
      e.target.value = v;
    });
  }

  // Submeter CEP no Modal
  if (modalCepForm && modalCepInput) {
    modalCepForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const rawCep = modalCepInput.value.replace(/\D/g, '');

      if (rawCep.length !== 8) {
        if (modalCepError) {
          modalCepError.textContent = 'Por favor, informe um CEP válido com 8 dígitos.';
          modalCepError.style.display = 'block';
        }
        return;
      }

      if (modalCepError) {
        modalCepError.style.display = 'none';
      }

      const formattedCep = rawCep.substring(0, 5) + '-' + rawCep.substring(5, 8);

      try {
        let data = null;
        try {
          const resLocal = await fetch(`api/cep.php?cep=${rawCep}`);
          if (resLocal.ok) data = await resLocal.json();
        } catch (e) {}

        if (!data || data.erro) {
          const resVia = await fetch(`https://viacep.com.br/ws/${rawCep}/json/`);
          data = await resVia.json();
        }

        if (!data || data.erro) {
          if (modalCepError) {
            modalCepError.textContent = 'CEP não encontrado. Tente novamente.';
            modalCepError.style.display = 'block';
          }
        } else {
          const city = data.localidade || 'Sua Cidade';
          const state = data.uf ? `/${data.uf}` : '';
          const cityState = `${city}${state}`;

          try {
            localStorage.setItem('userCep', formattedCep);
            localStorage.setItem('userCityState', cityState);
          } catch(e) {}
          
          // Atualizar o texto da barra superior para:
          // Entregar em: [CEP] - [CIDADE]
          if (cepTextContainer) {
            cepTextContainer.innerHTML = `
              <span class="cep-label-top" style="font-size: 12px; color: #ffffff; font-weight: 400; display: block; line-height: 1.2;">Entregar em:</span>
              <span class="cep-value-bottom" style="font-size: 14px; color: #ffffff; font-weight: 700; display: block; line-height: 1.2;">${formattedCep} - ${cityState}</span>
            `;
          }

          // Preencher o input do card de frete
          if (pdpCepInput) {
            pdpCepInput.value = formattedCep;
          }

          // Exibir o resultado no card de frete abaixo da página
          if (pdpShippingResult) {
            pdpShippingResult.style.display = 'block';
            if (pdpShippingCity) {
              pdpShippingCity.textContent = cityState;
            }
          }

          // Atualizar texto na barra fixa inferior
          const stickyDeliveryText = document.getElementById('stickyDeliveryText');
          if (stickyDeliveryText) {
            stickyDeliveryText.innerHTML = `Receba em <strong>${city}</strong> com <strong>Frete Grátis</strong>.`;
          }

          // Fechar modal
          if (cepModalOverlay) {
            cepModalOverlay.style.display = 'none';
            cepModalOverlay.classList.remove('active');
          }
        }
      } catch (err) {
        if (modalCepError) {
          modalCepError.textContent = 'Erro ao consultar CEP. Tente novamente.';
          modalCepError.style.display = 'block';
        }
      }
    });
  }

  // Inicializar localização do usuário salva ao carregar a página
  function checkUserLocation() {
    var savedCep = localStorage.getItem('userCep');
    var savedCityState = localStorage.getItem('userCityState');
    var cepContainer = document.getElementById('cepTextContainer');
    var pdpCepInput = document.getElementById('pdpCepInput');
    var pdpShippingResult = document.getElementById('pdpShippingResult');
    var pdpShippingCity = document.getElementById('pdpShippingCity');
    var stickyDeliveryText = document.getElementById('stickyDeliveryText');

    if (savedCep && savedCityState && cepContainer) {
      var formattedCep = savedCep.replace(/\D/g, '');
      if (formattedCep.length === 8) {
        formattedCep = formattedCep.substring(0, 5) + '-' + formattedCep.substring(5);
      } else {
        formattedCep = savedCep;
      }
      cepContainer.innerHTML = `
        <span class="cep-label-top" style="font-size: 12px; color: #ffffff; font-weight: 400; display: block; line-height: 1.2;">Entregar em:</span>
        <span class="cep-value-bottom" style="font-size: 14px; color: #ffffff; font-weight: 700; display: block; line-height: 1.2;">${formattedCep} - ${savedCityState}</span>
      `;

      if (pdpCepInput) pdpCepInput.value = formattedCep;
      if (pdpShippingResult) {
        pdpShippingResult.style.display = 'block';
        if (pdpShippingCity) pdpShippingCity.textContent = savedCityState;
      }
      if (stickyDeliveryText) {
        var cityName = savedCityState.split('/')[0] || savedCityState;
        stickyDeliveryText.innerHTML = `Receba em <strong>${cityName}</strong> com <strong>Frete Grátis</strong>.`;
      }
    } else {
      if (cepContainer) {
        cepContainer.innerHTML = `<span class="cep-label-only">Informe seu CEP</span>`;
      }
      if (pdpCepInput) pdpCepInput.value = '';
      if (pdpShippingResult) pdpShippingResult.style.display = 'none';
      if (stickyDeliveryText) stickyDeliveryText.innerHTML = '';
    }
  }

  checkUserLocation();
  window.checkUserLocation = checkUserLocation;

  // 1. Galeria de Imagens
  const mainImage = document.getElementById('mainProductImage');
  const thumbItems = document.querySelectorAll('.thumb-item');

  thumbItems.forEach(thumb => {
    thumb.addEventListener('click', function() {
      thumbItems.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      
      const newSrc = this.dataset.src;
      if (mainImage && newSrc) {
        mainImage.src = newSrc;
      }
    });
  });

  // 2. Seleção de Garantia Estendida
  const warrantyCards = document.querySelectorAll('.warranty-card');

  warrantyCards.forEach(card => {
    card.addEventListener('click', function() {
      warrantyCards.forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');

      const price = parseFloat(this.dataset.price || 0);
      updatePrices(price);
    });
  });

  function updatePrices(extraMonthlyPrice) {
    const mainPixElement = document.getElementById('mainPixPrice');
    const stickyPixElement = document.getElementById('stickyPixPrice');
    
    if (mainPixElement) {
      const basePixPrice = parseFloat(mainPixElement.dataset.basePrice);
      const newTotal = basePixPrice + (extraMonthlyPrice * 12);
      
      const formatted = newTotal.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      }).replace('R$', '').trim();

      mainPixElement.textContent = `R$ ${formatted}`;
      if (stickyPixElement) {
        stickyPixElement.textContent = formatted;
      }
    }
  }

  // 3. Barra Fixa Inferior (Sticky Bottom Bar) acionada assim que o cliente rola para baixo (idêntico à montagem do usuário)
  const stickyBottomBar = document.getElementById('stickyBottomBar');

  if (stickyBottomBar) {
    const checkScroll = () => {
      // Assim que o cliente rolar mais de 80px para baixo (iniciando a leitura da foto do produto), a barra fica fixa no rodapé
      if (window.scrollY > 80) {
        stickyBottomBar.classList.add('visible');
      } else {
        stickyBottomBar.classList.remove('visible');
      }
    };
    
    window.addEventListener('scroll', checkScroll, { passive: true });
    window.addEventListener('resize', checkScroll, { passive: true });
    checkScroll();
  }
});

function handleBuy(type) {
  var urlParams = new URLSearchParams(window.location.search);
  var prodId = urlParams.get('id') || '1';
  window.location.href = 'carrinho.php?id=' + encodeURIComponent(prodId);
}
