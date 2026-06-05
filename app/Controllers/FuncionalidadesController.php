<?php
/**
 * FuncionalidadesController - Cadastro das páginas do sistema
 */

require_once BASE_PATH . '/app/Models/FuncionalidadeModel.php';

class FuncionalidadesController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();

        $model = new FuncionalidadeModel();
        $funcionalidades = $model->listarTodas(false);

        $content = $this->renderFuncionalidades($funcionalidades);

        $this->view('funcionalidades.index', [
            'pageTitle' => 'Funcionalidades',
            'currentPage' => 'funcionalidades',
            'content' => $content,
        ]);
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('funcionalidades');
            return;
        }
        try {
            $model = new FuncionalidadeModel();
            $model->salvar(
                $_POST['nome'] ?? '',
                $_POST['rota'] ?? '',
                !empty($_POST['ativo'])
            );
            $_SESSION['flash_sucesso'] = 'Funcionalidade cadastrada com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao cadastrar funcionalidade.';
        }
        $this->redirect('funcionalidades');
    }

    public function atualizar(string $id): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('funcionalidades');
            return;
        }
        try {
            $model = new FuncionalidadeModel();
            $model->atualizar(
                (int)$id,
                $_POST['nome'] ?? '',
                $_POST['rota'] ?? '',
                !empty($_POST['ativo'])
            );
            $_SESSION['flash_sucesso'] = 'Funcionalidade atualizada com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao atualizar funcionalidade.';
        }
        $this->redirect('funcionalidades');
    }

    private function renderFuncionalidades(array $funcionalidades): string
    {
        $baseUrl = BASE_URL;
        ob_start();
        ?>
        <div class="card">
          <div style="margin-bottom:16px;">
            <button class="btn btn-primary" onclick="abrirModal('modalCadastroFuncionalidade')">+ Cadastrar Funcionalidade</button>
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>Rota</th>
                  <th>Status</th>
                  <th style="text-align:center;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($funcionalidades)): ?>
                <tr>
                  <td colspan="4" style="text-align:center;color:var(--color-text-secondary);">Nenhuma funcionalidade cadastrada</td>
                </tr>
                <?php else: ?>
                <?php foreach ($funcionalidades as $f): ?>
                <tr>
                  <td><?= htmlspecialchars($f['nome']) ?></td>
                  <td><?= htmlspecialchars($f['rota']) ?></td>
                  <td><?= $f['ativo'] ? '<span style="color:var(--color-primary);">Ativo</span>' : '<span style="color:#999;">Inativo</span>' ?></td>
                  <td style="text-align:center;">
                    <button class="btn-icon btn-edit" title="Editar" onclick="abrirModalEditarFunc(<?= (int)$f['id'] ?>, '<?= htmlspecialchars(addslashes($f['nome'])) ?>', '<?= htmlspecialchars(addslashes($f['rota'])) ?>', <?= $f['ativo'] ? 'true' : 'false' ?>)">
                      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Cadastro Funcionalidade -->
        <div id="modalCadastroFuncionalidade" class="modal" tabindex="-1">
          <div class="modal-dialog modal-draggable">
            <div class="modal-content">
              <div class="modal-header" data-drag-handle>
                <h4 class="modal-title">Cadastrar Funcionalidade</h4>
                <button type="button" class="close" onclick="fecharModal('modalCadastroFuncionalidade')" aria-label="Fechar">&#10006;</button>
              </div>
              <form method="POST" action="<?= $baseUrl ?>funcionalidades/salvar">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="nome_funcionalidade">Nome da Funcionalidade</label>
                    <input type="text" id="nome_funcionalidade" name="nome" class="form-control" placeholder="Ex: Dashboard, Relatórios" required>
                  </div>
                  <div class="form-group">
                    <label for="rota">Rota</label>
                    <input type="text" id="rota" name="rota" class="form-control" placeholder="Ex: home/index, relatorios/index" required>
                  </div>
                  <div class="form-group">
                    <label>
                      <input type="checkbox" name="ativo" value="1" checked> Ativo
                    </label>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" onclick="fecharModal('modalCadastroFuncionalidade')">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Modal Editar Funcionalidade -->
        <div id="modalEditarFuncionalidade" class="modal" tabindex="-1">
          <div class="modal-dialog modal-draggable">
            <div class="modal-content">
              <div class="modal-header" data-drag-handle>
                <h4 class="modal-title">Editar Funcionalidade</h4>
                <button type="button" class="close" onclick="fecharModal('modalEditarFuncionalidade')" aria-label="Fechar">&#10006;</button>
              </div>
              <form id="formEditarFuncionalidade" method="POST" action="">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="edit_nome_func">Nome da Funcionalidade</label>
                    <input type="text" id="edit_nome_func" name="nome" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label for="edit_rota">Rota</label>
                    <input type="text" id="edit_rota" name="rota" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label>
                      <input type="checkbox" id="edit_ativo" name="ativo" value="1"> Ativo
                    </label>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" onclick="fecharModal('modalEditarFuncionalidade')">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <script>
        function abrirModalEditarFunc(id, nome, rota, ativo) {
          document.getElementById('edit_nome_func').value = nome;
          document.getElementById('edit_rota').value = rota;
          document.getElementById('edit_ativo').checked = ativo;
          document.getElementById('formEditarFuncionalidade').action = '<?= $baseUrl ?>funcionalidades/atualizar/' + id;
          abrirModal('modalEditarFuncionalidade');
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
