<?php
$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR" id="html-theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SETAS-WEB</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/app.css" rel="stylesheet">
</head>
<body>
  <div class="login-container">
    <div class="card login-card">
      <h1><span>SETAS</span>-WEB</h1>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form action="<?= BASE_URL ?>auth/autenticar" method="POST">
        <div class="form-group">
          <label for="cpf">CPF</label>
          <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" 
                 maxlength="14" required 
                 value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="senha">Senha</label>
          <div class="password-wrapper">
            <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
            <button type="button" class="password-toggle" aria-label="Mostrar senha">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
              </svg>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" name="manter_logado" value="1"> Manter logado (30 dias)
          </label>
        </div>
        <div class="form-group">
          <button type="submit" class="btn btn-primary" style="width:100%;">Entrar</button>
        </div>
        <div style="text-align:center;margin-top:16px;">
          <a href="<?= BASE_URL ?>auth/lembrar-senha" style="color:var(--color-primary);">Esqueci minha senha</a>
        </div>
      </form>
    </div>
  </div>
  <div style="position:fixed;top:16px;right:16px;">
    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Alternar tema">
      <svg class="icon-sun" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1z"/></svg>
      <svg class="icon-moon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z"/></svg>
    </button>
  </div>
  <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
