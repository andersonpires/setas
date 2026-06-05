<?php
$pageTitle = $pageTitle ?? 'Editar Colaborador';
$currentPage = $currentPage ?? 'colaboradores';
$colaborador = $colaborador ?? null;
$permissoes = $permissoes ?? [];

function formatarCpf(string $cpf): string {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

ob_start();
?>
<div class="card">
  <h4 class="card-title-benef">Editar Colaborador</h4>
  <?php if ($colaborador): ?>
  <form method="POST" action="<?= BASE_URL ?>colaboradores/atualizar/<?= $colaborador['id'] ?>">
    <div class="form-group">
      <label for="nome">Nome completo</label>
      <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($colaborador['nome']) ?>" required>
    </div>
    <div class="form-group">
      <label for="cpf">CPF</label>
      <input type="text" id="cpf" name="cpf" class="form-control" maxlength="14" value="<?= htmlspecialchars(formatarCpf($colaborador['cpf'] ?? '')) ?>" required>
    </div>
    <div class="form-group">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($colaborador['email']) ?>" required>
    </div>
    <div class="form-group">
      <label for="dt_nascimento">Data de Nascimento</label>
      <input type="date" id="dt_nascimento" name="dt_nascimento" class="form-control" value="<?= htmlspecialchars($colaborador['dt_nascimento'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label for="permissao_id">Permissão</label>
      <select id="permissao_id" name="permissao_id" class="form-control" required>
        <?php foreach ($permissoes as $p): ?>
        <option value="<?= $p['id'] ?>" <?= ($colaborador['permissao_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="nova_senha">Nova senha (deixe em branco para manter a atual)</label>
      <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="Opcional">
    </div>
    <div style="margin-top:20px;display:flex;gap:12px;">
      <button type="submit" class="btn btn-primary">Salvar alterações</button>
      <a href="<?= BASE_URL ?>colaboradores" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
  <?php else: ?>
  <p>Colaborador não encontrado.</p>
  <a href="<?= BASE_URL ?>colaboradores" class="btn btn-secondary">Voltar</a>
  <?php endif; ?>
</div>
<script>
document.getElementById('cpf')?.addEventListener('input', function() {
  let v = this.value.replace(/\D/g, '');
  this.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4').slice(0, 14);
});
</script>
<?php
$content = ob_get_clean();
$subtitle = 'Alterar dados do colaborador';
require BASE_PATH . '/app/template/layout.php';