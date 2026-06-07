<?php
/**
 * Layout principal - Estrutura base com navbar, sidebar e conteúdo
 * Variáveis: $pageTitle, $currentPage, $menuItems, $content (ou renderização de view)
 */
$pageTitle = $pageTitle ?? 'SETAS-WEB';
$currentPage = $currentPage ?? 'home';
$menuItems = $menuItems ?? [];
$showSidebar = $showSidebar ?? true;
$showNavbar = $showNavbar ?? true;

// Menu padrão (filtrar por permissão pode ser feito no controller)
$defaultMenu = [
    ['key' => 'home', 'label' => 'Início', 'url' => 'home', 'icon' => '🏠'],
    ['key' => 'colaboradores', 'label' => 'Colaboradores', 'url' => 'colaboradores', 'icon' => '👥'],
    ['key' => 'beneficiario', 'label' => 'Beneficiário', 'url' => 'beneficiario', 'icon' => '📋'],
    ['key' => 'permissoes', 'label' => 'Permissões', 'url' => 'permissoes', 'icon' => '🔐'],
    ['key' => 'funcionalidades', 'label' => 'Funcionalidades', 'url' => 'funcionalidades', 'icon' => '📄'],
];
$menuItems = !empty($menuItems) ? $menuItems : $defaultMenu;
?>
<!DOCTYPE html>
<html lang="pt-BR" id="html-theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - SETAS-WEB</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(BASE_PATH . '/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
  <div id="loadingOverlay" class="loading-overlay" aria-hidden="true">
    <div class="loading-overlay-box">
      <div class="loading-overlay-spinner"></div>
      <p class="loading-overlay-text">Buscando dados...</p>
    </div>
  </div>
  <?php if ($showNavbar): require BASE_PATH . '/app/template/navbar.php'; endif; ?>
  <?php if ($showSidebar): require BASE_PATH . '/app/template/sidebar.php'; endif; ?>
  <main class="main-content <?= $showSidebar ? '' : 'expanded-layout' ?>" id="mainContent">
    <?php if ($showNavbar): require BASE_PATH . '/app/template/header.php'; endif; ?>
    <?php if (!empty($_SESSION['flash_sucesso'])): ?>
    <div class="alert alert-success alert-dismissible flash-msg" role="alert">
      <button type="button" class="close" data-dismiss="alert" onclick="this.parentElement.remove()">&times;</button>
      <?= htmlspecialchars($_SESSION['flash_sucesso']) ?>
    </div>
    <?php unset($_SESSION['flash_sucesso']); endif; ?>
    <?php if (!empty($_SESSION['flash_erro'])): ?>
    <div class="alert alert-danger alert-dismissible flash-msg" role="alert">
      <button type="button" class="close" data-dismiss="alert" onclick="this.parentElement.remove()">&times;</button>
      <?= htmlspecialchars($_SESSION['flash_erro']) ?>
    </div>
    <?php unset($_SESSION['flash_erro']); endif; ?>
    <?= $content ?? '' ?>
  </main>
  <?php require BASE_PATH . '/app/template/footer.php'; ?>
  <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
  <script src="<?= BASE_URL ?>assets/js/app.js"></script>
  <?= $scripts ?? '' ?>
</body>
</html>
