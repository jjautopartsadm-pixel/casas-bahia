<?php
// Buscar os 5 produtos cadastrados no Admin no banco MySQL
$searchTopProducts = [];
try {
    if (isset($db) && $db) {
        $stmtSearch = $db->query("SELECT id, title FROM products ORDER BY id DESC LIMIT 5");
        $searchTopProducts = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

// Fallback caso haja menos de 5 produtos cadastrados no Admin
$defaultFallbacks = [
    ['id' => 1, 'title' => 'Smart TV 43" AOC Full HD Wi-Fi Roku TV'],
    ['id' => 2, 'title' => 'Smart TV 55" TCL 4K QD-Mini LED 144Hz'],
    ['id' => 3, 'title' => 'iPhone 15 Apple (128GB) Preto Tela 6,1"'],
    ['id' => 4, 'title' => 'Smartphone Samsung Galaxy S24 Ultra 256GB'],
    ['id' => 5, 'title' => 'Fritadeira Air Fryer Mondial 4L Inox']
];

while (count($searchTopProducts) < 5) {
    $fb = $defaultFallbacks[count($searchTopProducts)];
    $searchTopProducts[] = $fb;
}
?>
<style>
  /* ESTILOS DO MODAL DE PESQUISA AUTOCOMPLETE SLEEK & ELEGANTES */
  .search-box-wrapper {
    position: relative !important;
    z-index: 1000;
  }
  
  .search-autocomplete-modal {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 30, 80, 0.12);
    border: 1px solid #e2e8f0;
    padding: 12px 10px;
    display: none;
    z-index: 99999;
    animation: searchFadeIn 0.18s ease-out;
  }
  
  @keyframes searchFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .search-modal-title {
    font-size: 13px;
    font-weight: 800;
    color: #003399;
    margin-bottom: 8px;
    padding-left: 6px;
    letter-spacing: -0.2px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }

  .search-results-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .search-result-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: all 0.15s ease;
    cursor: pointer;
  }

  .search-result-item:hover, .search-result-item:focus {
    background-color: #f1f5f9;
    transform: translateX(2px);
  }

  .search-badge-num {
    width: 22px;
    height: 22px;
    border-radius: 5px;
    background-color: #003399;
    color: #ffffff;
    font-size: 11px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0, 51, 153, 0.18);
  }

  .search-item-text {
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.25;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }
</style>

<script>
  window.searchTopProductsData = <?= json_encode($searchTopProducts, JSON_UNESCAPED_UNICODE) ?>;

  document.addEventListener('DOMContentLoaded', function() {
    initSearchAutocomplete();
  });

  function initSearchAutocomplete() {
    const searchInputs = document.querySelectorAll('.search-input-container input');

    searchInputs.forEach(input => {
      const wrapper = input.closest('.search-box-wrapper') || input.parentElement;
      
      // Criar modal se não existir no container
      let modal = wrapper.querySelector('.search-autocomplete-modal');
      if (!modal) {
        modal = document.createElement('div');
        modal.className = 'search-autocomplete-modal';
        wrapper.appendChild(modal);
      }

      renderSearchItems(modal, window.searchTopProductsData);

      // Abrir modal ao focar ou clicar no campo de busca
      input.addEventListener('focus', function() {
        renderSearchItems(modal, filterSearchData(input.value));
        modal.style.display = 'block';
      });

      input.addEventListener('click', function() {
        renderSearchItems(modal, filterSearchData(input.value));
        modal.style.display = 'block';
      });

      // Filtrar em tempo real conforme o cliente digita
      input.addEventListener('input', function() {
        renderSearchItems(modal, filterSearchData(input.value));
        modal.style.display = 'block';
      });

      // Tecla Enter
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          const q = input.value.trim();
          if (q) {
            window.location.href = 'categoria.php?cat=tvs';
          }
        }
      });
    });

    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.search-box-wrapper') && !e.target.closest('.search-input-container')) {
        document.querySelectorAll('.search-autocomplete-modal').forEach(m => {
          m.style.display = 'none';
        });
      }
    });
  }

  function filterSearchData(query) {
    if (!query || !query.trim()) {
      return window.searchTopProductsData.slice(0, 5);
    }
    const q = query.toLowerCase().trim();
    const filtered = window.searchTopProductsData.filter(item => 
      item.title.toLowerCase().includes(q)
    );
    if (filtered.length > 0) {
      return filtered.slice(0, 5);
    }
    return window.searchTopProductsData.slice(0, 5);
  }

  function renderSearchItems(modalEl, items) {
    let html = '<div class="search-modal-title">Termos mais buscados</div>';
    html += '<ul class="search-results-list">';
    
    items.slice(0, 5).forEach((item, index) => {
      html += `
        <li>
          <a href="index.php?id=${item.id}" class="search-result-item">
            <span class="search-badge-num">${index + 1}</span>
            <span class="search-item-text">${escapeHtml(item.title)}</span>
          </a>
        </li>
      `;
    });
    
    html += '</ul>';
    modalEl.innerHTML = html;
  }

  function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }
</script>
