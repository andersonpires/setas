<?php
/**
 * Navbar - Barra superior
 * Variáveis esperadas: $pageTitle (string), $showMenuButton (bool)
 */
$pageTitle = $pageTitle ?? 'SETAS';
$showMenuButton = $showMenuButton ?? true;

$navbarFotoUrl = null;
if (!empty($_SESSION['colaborador_id'])) {
    require_once BASE_PATH . '/app/Models/ColaboradorModel.php';
    $colab = (new ColaboradorModel())->buscarPorId((int) $_SESSION['colaborador_id']);
    if ($colab) {
        $cpfN = preg_replace('/\D/', '', $colab['cpf'] ?? '');
        $fotoRel = $colab['foto'] ?? ('assets/img/foto_colaborador/' . $cpfN . '.jpg');
        $fotoAbs = BASE_PATH . '/' . ltrim($fotoRel, '/');
        if (file_exists($fotoAbs)) {
            $navbarFotoUrl = BASE_URL . ltrim($fotoRel, '/');
        }
    }
}
?>
<nav class="navbar">
  <div class="navbar-left">
    <?php if ($showMenuButton): ?>
      <button type="button" class="navbar-menu-btn" id="sidebarToggle" aria-label="Alternar menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
        </svg>
      </button>
    <?php endif; ?>
    <?php if (file_exists(BASE_PATH . '/assets/logo_sistema/logo.png')): ?>
      <a href="<?= BASE_URL ?>home" class="navbar-logo-link">
        <picture>
          <source media="(max-width: 768px)" srcset="<?= BASE_URL ?>assets/logo_sistema/logo-mobile.png">
          <img src="<?= BASE_URL ?>assets/logo_sistema/logo.png" alt="SETAS-WEB" class="navbar-logo">
        </picture>
      </a>
    <?php endif; ?>
  </div>
  <a href="<?= BASE_URL ?>home" class="navbar-brand">
    <span>SETAS</span>-WEB
  </a>
  <div class="navbar-right">
    <!-- Desktop: botões individuais -->
    <div class="navbar-actions-desktop">
      <button type="button" class="theme-toggle navbar-menu-btn" id="themeToggle" aria-label="Alternar tema claro/escuro">
        <svg class="icon-sun" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
          <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 0 0 0-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
        </svg>
        <svg class="icon-moon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/>
        </svg>
      </button>
      <?php if (!empty($_SESSION['colaborador_id'])): ?>
        <a href="<?= BASE_URL ?>perfil" class="navbar-menu-btn navbar-avatar-link" title="Perfil">
          <?php if ($navbarFotoUrl): ?>
            <img src="<?= htmlspecialchars($navbarFotoUrl) ?>" alt="Foto do perfil" class="navbar-avatar">
          <?php else: ?>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>auth/logout" class="navbar-menu-btn" title="Sair">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
          </svg>
        </a>
      <?php endif; ?>
    </div>
    <!-- Mobile: menu suspenso -->
    <div class="navbar-actions-mobile">
      <button type="button" class="navbar-menu-btn navbar-dropdown-toggle" id="navbarDropdownToggle" aria-label="Menu de opções">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
        </svg>
      </button>
      <div class="navbar-dropdown" id="navbarDropdown">
        <button type="button" class="navbar-dropdown-item theme-toggle-mobile" aria-label="Alternar tema">
          <span class="navbar-dropdown-icon">🌙</span>
          <span>Tema claro/escuro</span>
        </button>
        <?php if (!empty($_SESSION['colaborador_id'])): ?>
          <a href="<?= BASE_URL ?>perfil" class="navbar-dropdown-item">
            <?php if ($navbarFotoUrl): ?>
              <span class="navbar-dropdown-icon navbar-dropdown-avatar"><img src="<?= htmlspecialchars($navbarFotoUrl) ?>" alt=""></span>
            <?php else: ?>
              <span class="navbar-dropdown-icon">👤</span>
            <?php endif; ?>
            <span>Perfil</span>
          </a>
          <a href="<?= BASE_URL ?>auth/logout" class="navbar-dropdown-item">
            <span class="navbar-dropdown-icon">🚪</span>
            <span>Sair</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
