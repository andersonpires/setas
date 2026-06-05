<?php
/**
 * PermissoesController - Gerenciar permissões e páginas permitidas
 */

require_once BASE_PATH . '/app/Models/PermissaoModel.php';
require_once BASE_PATH . '/app/Models/FuncionalidadeModel.php';

class PermissoesController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();

        $permModel = new PermissaoModel();
        $funcModel = new FuncionalidadeModel();
        $todas = $permModel->listarTodas();
        $permissoes = array_values(array_filter($todas, function ($p) {
            return ($p['nome'] ?? '') !== 'superadministrador';
        }));
        $funcionalidades = $funcModel->listarTodas(false);

        $vinculos = [];
        foreach ($permissoes as $p) {
            $vinculos[$p['id']] = $permModel->getFuncionalidadesVinculadas((int)$p['id']);
        }

        $content = $this->renderPermissoes($permissoes, $funcionalidades, $vinculos);

        $this->view('permissoes.index', [
            'pageTitle' => 'Permissões',
            'currentPage' => 'permissoes',
            'content' => $content,
        ]);
    }

    public function salvar(): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('permissoes');
            return;
        }
        try {
            $permModel = new PermissaoModel();
            $id = $permModel->salvar($_POST['nome'] ?? '');
            $ids = array_filter(array_map('intval', $_POST['funcionalidade_id'] ?? []));
            $permModel->vincularFuncionalidades($id, $ids);
            $_SESSION['flash_sucesso'] = 'Permissão cadastrada com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao cadastrar permissão.';
        }
        $this->redirect('permissoes');
    }

    public function atualizar(string $id): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('permissoes');
            return;
        }
        try {
            $permModel = new PermissaoModel();
            $permModel->atualizar((int)$id, $_POST['nome'] ?? '');
            $ids = array_filter(array_map('intval', $_POST['funcionalidade_id'] ?? []));
            $permModel->vincularFuncionalidades((int)$id, $ids);
            $_SESSION['flash_sucesso'] = 'Permissão atualizada com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao atualizar permissão.';
        }
        $this->redirect('permissoes');
    }

    public function excluir(string $id): void
    {
        $this->exigirLogin();
        try {
            $permModel = new PermissaoModel();
            $permModel->excluir((int)$id);
            $_SESSION['flash_sucesso'] = 'Permissão excluída com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao excluir permissão.';
        }
        $this->redirect('permissoes');
    }

    private function renderPermissoes(array $permissoes, array $funcionalidades, array $vinculos): string
    {
        $baseUrl = BASE_URL;
        ob_start();
        ?>
        <div class="card">
          <div style="margin-bottom:16px;">
            <button class="btn btn-primary" onclick="abrirModal('modalCadastroPermissao')">+ Cadastrar Permissão</button>
          </div>
          
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Nome da Permissão</th>
                  <th>Funcionalidades</th>
                  <th style="text-align:center;">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($permissoes as $p):
                  $vinc = $vinculos[$p['id']] ?? [];
                  $isSuper = ($p['nome'] ?? '') === 'superadministrador';
                  $lista = $isSuper ? 'Todas' : (empty($vinc) ? 'Nenhuma' : count($vinc) . ' vinculada(s)');
                ?>
                <tr>
                  <td><?= htmlspecialchars($p['nome']) ?></td>
                  <td><em><?= htmlspecialchars($lista) ?></em></td>
                  <td style="text-align:center;">
                    <?php if (!$isSuper): ?>
                    <button type="button" class="btn-icon btn-edit btn-editar-permissao" title="Editar" data-id="<?= (int)$p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>" data-vinculos="<?= htmlspecialchars(json_encode(array_map('intval', $vinc))) ?>">
                      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    </button>
                    <button type="button" class="btn-icon btn-delete btn-excluir-permissao" title="Excluir" data-id="<?= (int)$p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>">
                      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Cadastro Permissão -->
        <div id="modalCadastroPermissao" class="modal" tabindex="-1">
          <div class="modal-dialog modal-draggable">
            <div class="modal-content">
              <div class="modal-header" data-drag-handle>
                <h4 class="modal-title">Cadastrar Permissão</h4>
                <button type="button" class="close" onclick="fecharModal('modalCadastroPermissao')" aria-label="Fechar">&#10006;</button>
              </div>
              <form method="POST" action="<?= $baseUrl ?>permissoes/salvar">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="nome_permissao">Nome da Permissão</label>
                    <input type="text" id="nome_permissao" name="nome" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label>Funcionalidades permitidas</label>
                    <div class="checkbox-list">
                      <?php foreach ($funcionalidades as $f): ?>
                      <label class="checkbox-item"><input type="checkbox" name="funcionalidade_id[]" value="<?= $f['id'] ?>"> <?= htmlspecialchars($f['nome']) ?> (<?= htmlspecialchars($f['rota']) ?>)</label>
                      <?php endforeach; ?>
                      <?php if (empty($funcionalidades)): ?>
                      <p style="color:var(--color-text-secondary);font-size:0.875rem;">Nenhuma funcionalidade cadastrada.</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" onclick="fecharModal('modalCadastroPermissao')">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Modal Editar Permissão -->
        <div id="modalEditarPermissao" class="modal" tabindex="-1">
          <div class="modal-dialog modal-draggable">
            <div class="modal-content">
              <div class="modal-header" data-drag-handle>
                <h4 class="modal-title">Editar Permissão</h4>
                <button type="button" class="close" onclick="fecharModal('modalEditarPermissao')" aria-label="Fechar">&#10006;</button>
              </div>
              <form id="formEditarPermissao" method="POST" action="">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="edit_nome_permissao">Nome da Permissão</label>
                    <input type="text" id="edit_nome_permissao" name="nome" class="form-control" required>
                  </div>
                  <div class="form-group">
                    <label>Funcionalidades permitidas</label>
                    <div class="checkbox-list" id="editCheckboxes">
                      <?php foreach ($funcionalidades as $f): ?>
                      <label class="checkbox-item"><input type="checkbox" name="funcionalidade_id[]" value="<?= $f['id'] ?>" data-fid="<?= $f['id'] ?>"> <?= htmlspecialchars($f['nome']) ?> (<?= htmlspecialchars($f['rota']) ?>)</label>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" onclick="fecharModal('modalEditarPermissao')">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <script>
        (function() {
          var baseUrl = <?= json_encode($baseUrl) ?>;
          document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-editar-permissao');
            if (btn) {
              var id = parseInt(btn.getAttribute('data-id'), 10);
              var nome = btn.getAttribute('data-nome') || '';
              var vinculos = [];
              try { vinculos = JSON.parse(btn.getAttribute('data-vinculos') || '[]'); } catch (x) {}
              document.getElementById('edit_nome_permissao').value = nome;
              document.getElementById('formEditarPermissao').action = baseUrl + 'permissoes/atualizar/' + id;
              document.querySelectorAll('#editCheckboxes input[type="checkbox"]').forEach(function(cb) {
                var fid = parseInt(cb.value, 10);
                cb.checked = vinculos.indexOf(fid) !== -1;
              });
              abrirModal('modalEditarPermissao');
              return;
            }
            btn = e.target.closest('.btn-excluir-permissao');
            if (btn) {
              var id = parseInt(btn.getAttribute('data-id'), 10);
              var nome = btn.getAttribute('data-nome') || '';
              if (!confirm('Excluir a permissão "' + nome + '"? Não é possível desfazer.')) return;
              var f = document.createElement('form');
              f.method = 'POST';
              f.action = baseUrl + 'permissoes/excluir/' + id;
              document.body.appendChild(f);
              f.submit();
            }
          });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
