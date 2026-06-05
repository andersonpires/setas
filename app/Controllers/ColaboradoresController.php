<?php
/**
 * ColaboradoresController
 */

require_once BASE_PATH . '/app/Models/ColaboradorModel.php';
require_once BASE_PATH . '/app/Models/PermissaoModel.php';

class ColaboradoresController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();
        $model = new ColaboradorModel();
        $permModel = new PermissaoModel();
        $colaboradores = array_values(array_filter(
            $model->listarTodos(true),
            function (array $c): bool {
                $nomePermissao = strtolower((string)($c['permissao_nome'] ?? ''));
                $idPermissao = (int)($c['permissao_id'] ?? 0);
                return $nomePermissao !== 'superadministrador' && $idPermissao !== 1;
            }
        ));
        $permissoes = $permModel->listarTodas();

        $content = $this->renderColaboradoresTable($colaboradores, $permissoes);
        $this->view('colaboradores.index', [
            'pageTitle' => 'Colaboradores',
            'currentPage' => 'colaboradores',
            'content' => $content,
            'subtitle' => 'Listar e gerenciar colaboradores',
        ]);
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('colaboradores');
            return;
        }
        try {
            $model = new ColaboradorModel();
            $model->salvar($_POST);
            $_SESSION['flash_sucesso'] = 'Colaborador cadastrado com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao cadastrar colaborador.';
        }
        $this->redirect('colaboradores');
    }

    public function editar(string $id): void
    {
        $this->exigirLogin();
        $model = new ColaboradorModel();
        $permModel = new PermissaoModel();
        $colab = $model->buscarPorId((int)$id);
        if (!$colab) {
            $_SESSION['flash_erro'] = 'Colaborador não encontrado.';
            $this->redirect('colaboradores');
            return;
        }
        $permissoes = $permModel->listarTodas();
        $this->view('colaboradores.editar', [
            'pageTitle' => 'Editar Colaborador',
            'currentPage' => 'colaboradores',
            'colaborador' => $colab,
            'permissoes' => $permissoes,
        ]);
    }

    public function atualizar(string $id): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('colaboradores');
            return;
        }
        try {
            $model = new ColaboradorModel();
            $model->atualizar((int)$id, $_POST);
            $_SESSION['flash_sucesso'] = 'Colaborador atualizado com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
            $this->redirect('colaboradores/editar/' . $id);
            return;
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao atualizar colaborador.';
        }
        $this->redirect('colaboradores');
    }

    public function ativar(string $id): void
    {
        $this->exigirLogin();
        try {
            $model = new ColaboradorModel();
            $model->ativar((int)$id);
            $_SESSION['flash_sucesso'] = 'Colaborador ativado.';
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao ativar.';
        }
        $this->redirect('colaboradores');
    }

    public function inativar(string $id): void
    {
        $this->exigirLogin();
        try {
            $model = new ColaboradorModel();
            $model->inativar((int)$id);
            $_SESSION['flash_sucesso'] = 'Colaborador inativado.';
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao inativar.';
        }
        $this->redirect('colaboradores');
    }

    private function formatarCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    private function formatarCpfMascarado(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        return '***.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-**';
    }

    private function getFotoUrlColaborador(array $c): ?string
    {
        $cpfN = preg_replace('/\D/', '', $c['cpf'] ?? '');
        $fotoRel = $c['foto'] ?? ('assets/img/foto_colaborador/' . $cpfN . '.jpg');
        $fotoAbs = BASE_PATH . '/' . ltrim($fotoRel, '/');
        return file_exists($fotoAbs) ? (BASE_URL . ltrim($fotoRel, '/')) : null;
    }

    private function renderColaboradoresTable(array $colaboradores, array $permissoes): string
    {
        ob_start();
        ?>
        <div class="card">
          <div style="margin-bottom:16px;">
            <button class="btn btn-primary" onclick="abrirModal('modalCadastroColaborador')">+ Cadastrar novo</button>
          </div>
          <div class="table-responsive">
            <input type="text" id="filtroColaborador" placeholder="Filtrar por nome, CPF ou email..." style="margin-bottom:12px;padding:8px;width:100%;max-width:400px;" class="form-control">
            <table class="table">
              <thead>
                <tr>
                  <th style="width:36px;"></th>
                  <th>Nome</th>
                  <th>CPF</th>
                  <th>Email</th>
                  <th>Permissão</th>
                  <th>Status</th>
                  <th style="text-align:center;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($colaboradores as $c):
                  $fotoUrl = $this->getFotoUrlColaborador($c);
                ?>
                <tr data-nome="<?= htmlspecialchars($c['nome']) ?>" data-cpf="<?= htmlspecialchars(preg_replace('/\D/', '', $c['cpf'] ?? '')) ?>" data-email="<?= htmlspecialchars($c['email']) ?>">
                  <td data-label="Foto" class="td-avatar">
                    <?php if ($fotoUrl): ?>
                      <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="" class="table-avatar">
                    <?php else: ?>
                      <span class="table-avatar table-avatar-icon" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td data-label="Nome"><?= htmlspecialchars($c['nome']) ?></td>
                  <td data-label="CPF"><?= htmlspecialchars($this->formatarCpfMascarado($c['cpf'] ?? '')) ?></td>
                  <td data-label="Email"><?= htmlspecialchars($c['email']) ?></td>
                  <td data-label="Permissão"><?= htmlspecialchars($c['permissao_nome'] ?? '-') ?></td>
                  <td data-label="Status"><?= $c['ativo'] ? '<span style="color:var(--color-primary);">Ativo</span>' : '<span style="color:#999;">Inativo</span>' ?></td>
                  <td style="text-align:center;white-space:nowrap;">
                    <button class="btn-icon btn-edit" title="Editar" onclick="editarColaborador(<?= $c['id'] ?>)">
                      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </button>
                    <button class="btn-icon <?= $c['ativo'] ? 'btn-inactive' : 'btn-active' ?>" title="<?= $c['ativo'] ? 'Inativar' : 'Ativar' ?>" onclick="toggleColaborador(<?= $c['id'] ?>, <?= $c['ativo'] ? 'false' : 'true' ?>)">
                      <?php if ($c['ativo']): ?>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.42 0 8 3.58 8 8 0 1.85-.63 3.55-1.69 4.9z"/></svg>
                      <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                      <?php endif; ?>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Cadastro Colaborador -->
        <div id="modalCadastroColaborador" class="modal" tabindex="-1">
          <div class="modal-dialog modal-draggable">
            <div class="modal-content">
              <div class="modal-header" data-drag-handle>
                <h4 class="modal-title">Cadastrar Colaborador</h4>
                <button type="button" class="close" onclick="fecharModal('modalCadastroColaborador')" aria-label="Fechar">&#10006;</button>
              </div>
              <form id="formCadastroColaborador" method="POST" action="<?= BASE_URL ?>colaboradores/salvar">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" class="form-control" maxlength="14" required>
                  </div>
                  <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label for="dt_nascimento">Data de Nascimento</label>
                    <input type="date" id="dt_nascimento" name="dt_nascimento" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label for="permissao_id">Permissão</label>
                    <select id="permissao_id" name="permissao_id" class="form-control" required>
                      <option value="">Selecione...</option>
                      <?php foreach ($permissoes as $p): ?>
                      <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" onclick="fecharModal('modalCadastroColaborador')">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <script>
        function editarColaborador(id) {
          window.location.href = '<?= BASE_URL ?>colaboradores/editar/' + id;
        }
        function toggleColaborador(id, ativar) {
          if (confirm(ativar ? 'Ativar este colaborador?' : 'Inativar este colaborador?')) {
            window.location.href = '<?= BASE_URL ?>colaboradores/' + (ativar ? 'ativar' : 'inativar') + '/' + id;
          }
        }
        document.getElementById('cpf')?.addEventListener('input', function() {
          let v = this.value.replace(/\D/g, '');
          this.value = v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4').slice(0, 14);
        });
        document.getElementById('filtroColaborador')?.addEventListener('input', function() {
          const v = this.value.toLowerCase();
          document.querySelectorAll('.table tbody tr').forEach(tr => {
            const nome = (tr.dataset.nome || '').toLowerCase();
            const cpf = (tr.dataset.cpf || '').replace(/\D/g, '');
            const email = (tr.dataset.email || '').toLowerCase();
            const match = nome.includes(v) || cpf.includes(v.replace(/\D/g, '')) || email.includes(v);
            tr.style.display = match ? '' : 'none';
          });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
