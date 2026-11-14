<?php
// admin/sidebar.php - Componente de Barra Lateral do Painel Admin
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo-admin">CASAS<span>BAHIA</span></div>
    <div class="sidebar-badge-tag">Painel Admin</div>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <li>
        <a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <span>Dashboard</span>
        </a>
      </li>

      <li>
        <a href="clientes.php" class="<?= ($current_page == 'clientes.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 1-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Clientes</span>
        </a>
      </li>

      <li>
        <a href="gateway.php" class="<?= ($current_page == 'gateway.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          <span>Gateway</span>
        </a>
      </li>

      <li>
        <a href="whatsapp.php" class="<?= ($current_page == 'whatsapp.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span>WhatsApp Z-API</span>
        </a>
      </li>

      <li>
        <a href="produtos.php" class="<?= ($current_page == 'produtos.php' || $current_page == 'product_form.php' || $current_page == 'import_product.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span>Produtos</span>
        </a>
      </li>

      <li>
        <a href="links.php" class="<?= ($current_page == 'links.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M10 12l2 2 4-4"/></svg>
          <span>Links & Bloqueio</span>
        </a>
      </li>

      <li>
        <a href="pixel.php" class="<?= ($current_page == 'pixel.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          <span>Pixel Facebook</span>
        </a>
      </li>

      <li>
        <a href="banners.php" class="<?= ($current_page == 'banners.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          <span>Banners Home</span>
        </a>
      </li>

      <li>
        <a href="categories.php" class="<?= ($current_page == 'categories.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9h16"/><path d="M4 15h16"/><rect x="2" y="3" width="20" height="18" rx="2"/></svg>
          <span>Categorias Home</span>
        </a>
      </li>

      <li>
        <a href="reviews.php" class="<?= ($current_page == 'reviews.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>
          <span>Avaliações c/ Fotos</span>
        </a>
      </li>

      <li>
        <a href="usuario.php" class="<?= ($current_page == 'usuario.php') ? 'active' : '' ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Usuário & Senha</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-user-footer">
    <a href="usuario.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; flex: 1;" title="Gerenciar Conta">
      <div class="user-avatar-circle"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
      <div class="user-details">
        <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrador') ?></span>
        <span class="user-role">Online (Editar)</span>
      </div>
    </a>
    <a href="logout.php" class="btn-sidebar-logout" title="Sair do painel">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</aside>
