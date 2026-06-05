<?php
/**
 * Header - Cabeçalho da página (título, breadcrumb, etc)
 * Variáveis esperadas: $pageTitle (string), $subtitle (string)
 */
$pageTitle = $pageTitle ?? '';
$subtitle = $subtitle ?? '';
?>
<header class="page-header">
  <?php if (!empty($pageTitle)): ?>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
  <?php endif; ?>
  <?php if (!empty($subtitle)): ?>
    <p class="page-subtitle" style="margin:8px 0 0 0;color:var(--color-text-secondary);font-size:0.9rem;">
      <?= htmlspecialchars($subtitle) ?>
    </p>
  <?php endif; ?>
</header>
