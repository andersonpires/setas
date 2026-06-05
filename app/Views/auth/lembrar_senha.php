<?php
$etapa = $etapa ?? 1;
$erro = $erro ?? '';
$sucesso = $sucesso ?? '';
$info = $info ?? '';
$tokenValue = $token ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR" id="html-theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lembrar Senha - SETAS-WEB</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>assets/css/app.css" rel="stylesheet">
</head>
<body>
  <div class="login-container">
    <div class="card login-card">
      <h1><span>SETAS</span>-WEB</h1>
      <h3>Recuperar senha</h3>
      <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <?php if ($info): ?>
        <div class="alert alert-info"><?= htmlspecialchars($info) ?></div>
      <?php endif; ?>
      <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
      <?php endif; ?>
      <?php if ($etapa === 3): ?>
        <p>Você já pode acessar o sistema utilizando a nova senha cadastrada.</p>
        <div class="form-group">
          <a class="btn btn-primary" href="<?= BASE_URL ?>auth/login">Ir para o login</a>
        </div>
      <?php else: ?>
        <form action="<?= BASE_URL ?>auth/recuperar-senha" method="POST">
          <?php if ($etapa === 1): ?>
            <p>Informe seu CPF e data de nascimento para validar sua identidade.</p>
            <div class="form-group">
              <label for="cpf">CPF</label>
              <input type="text" id="cpf" name="cpf" required>
            </div>
            <div class="form-group">
              <label for="dt_nascimento">Data de Nascimento</label>
              <input type="date" id="dt_nascimento" name="dt_nascimento" required>
            </div>
          <?php else: ?>
            <p>Cadastre sua nova senha.</p>
            <input type="hidden" name="token" value="<?= htmlspecialchars($tokenValue) ?>">
            <div class="form-group">
              <label for="nova_senha">Nova Senha</label>
              <div class="password-wrapper">
                <input type="password" id="nova_senha" name="nova_senha" required minlength="6">
                <button type="button" class="password-toggle" aria-label="Mostrar senha">👁</button>
              </div>
            </div>
            <div class="form-group">
              <label for="confirmar_senha">Confirmar Senha</label>
              <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>
          <?php endif; ?>
          <div class="form-group">
            <button type="submit" class="btn btn-primary"><?= $etapa === 1 ? 'Continuar' : 'Alterar senha' ?></button>
          </div>
          <p><a href="<?= BASE_URL ?>auth/login">Voltar ao login</a></p>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
