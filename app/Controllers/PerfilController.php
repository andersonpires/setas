<?php
/**
 * PerfilController - Dados do colaborador logado
 */

require_once BASE_PATH . '/app/Models/ColaboradorModel.php';
require_once BASE_PATH . '/vendor/ImageProcessor.php';

class PerfilController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();

        $model = new ColaboradorModel();
        $colaborador = $model->buscarPorId((int) $_SESSION['colaborador_id']);
        if (!$colaborador) {
            $_SESSION['flash_erro'] = 'Colaborador não encontrado.';
            $this->redirect('auth/logout');
        }

        $cpfNumeros = preg_replace('/\D/', '', $colaborador['cpf'] ?? '');
        $fotoRelativa = $colaborador['foto'] ?? ('assets/img/foto_colaborador/' . $cpfNumeros . '.jpg');
        $fotoAbs = BASE_PATH . '/' . ltrim($fotoRelativa, '/');
        if (file_exists($fotoAbs)) {
            $fotoUrl = BASE_URL . ltrim($fotoRelativa, '/');
        } else {
            $fotoUrl = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="160" height="160"%3E%3Crect width="100%25" height="100%25" fill="%23e5e7eb"/%3E%3Ctext x="50%25" y="52%25" text-anchor="middle" dominant-baseline="middle" font-family="Arial" font-size="18" fill="%23999"%3ESem%20foto%3C/text%3E%3C/svg%3E';
        }

        $content = $this->renderPerfilForm($colaborador, $fotoUrl);

        $this->view('perfil.index', [
            'pageTitle' => 'Perfil',
            'currentPage' => 'perfil',
            'content' => $content,
        ]);
    }

    public function atualizar(): void
    {
        $this->exigirLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('perfil');
        }

        $model = new ColaboradorModel();
        $colaborador = $model->buscarPorId((int) $_SESSION['colaborador_id']);
        if (!$colaborador) {
            $_SESSION['flash_erro'] = 'Colaborador não encontrado.';
            $this->redirect('auth/logout');
        }

        $fotoRelativa = null;
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_erro'] = 'Erro no upload da foto.';
                $this->redirect('perfil');
            }
            if (!is_uploaded_file($_FILES['foto']['tmp_name'])) {
                $_SESSION['flash_erro'] = 'Arquivo de foto inválido.';
                $this->redirect('perfil');
            }
            if (!empty($_FILES['foto']['size']) && $_FILES['foto']['size'] > 5 * 1024 * 1024) {
                $_SESSION['flash_erro'] = 'A foto deve ter no máximo 5MB.';
                $this->redirect('perfil');
            }

            $cpfNumeros = preg_replace('/\D/', '', $colaborador['cpf'] ?? '');
            $destDir = BASE_PATH . '/assets/img/foto_colaborador';
            $destPath = $destDir . '/' . $cpfNumeros . '.jpg';

            try {
                ImageProcessor::convertToJpeg($_FILES['foto']['tmp_name'], $destPath, 75, 800, 800);
                $fotoRelativa = 'assets/img/foto_colaborador/' . $cpfNumeros . '.jpg';
            } catch (Exception $e) {
                $_SESSION['flash_erro'] = $e->getMessage();
                $this->redirect('perfil');
            }
        }

        $dados = [
            'nome' => $_POST['nome'] ?? '',
            'email' => $_POST['email'] ?? '',
            'dt_nascimento' => $_POST['dt_nascimento'] ?? null,
            'nova_senha' => $_POST['nova_senha'] ?? '',
        ];

        try {
            $model->atualizarPerfil((int) $colaborador['id'], $dados, $fotoRelativa);
            $_SESSION['colaborador_nome'] = trim($dados['nome'] ?? $colaborador['nome']);
            $_SESSION['flash_sucesso'] = 'Perfil atualizado com sucesso.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_erro'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_erro'] = 'Erro ao atualizar perfil.';
        }

        $this->redirect('perfil');
    }

    private function formatarCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) {
            return $cpf;
        }
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    private function renderPerfilForm(array $colaborador, string $fotoUrl): string
    {
        ob_start();
        $cpfFormatado = $this->formatarCpf($colaborador['cpf'] ?? '');
        ?>
        <div class="card">
          <h4 style="margin-top:0;">Meu Perfil</h4>
          <form method="POST" action="<?= BASE_URL ?>perfil/atualizar" enctype="multipart/form-data">
            <div class="row">
              <div class="col-sm-4" style="text-align:center;margin-bottom:16px;">
                <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="Foto do colaborador" style="width:160px;height:160px;border-radius:50%;object-fit:cover;border:1px solid #e2e2e2;">
                <div style="margin-top:12px;">
                  <label for="foto" style="display:block;font-weight:600;">Foto do perfil</label>
                  <input type="file" id="foto" name="foto" accept="image/*" class="form-control">
                  <small class="text-muted">A foto será convertida para JPG e substituirá a anterior.</small>
                </div>
              </div>
              <div class="col-sm-8">
                <div class="form-group">
                  <label for="nome">Nome completo</label>
                  <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($colaborador['nome'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label for="cpf">CPF</label>
                  <input type="text" id="cpf" name="cpf" class="form-control" value="<?= htmlspecialchars($cpfFormatado) ?>" readonly>
                </div>
                <div class="form-group">
                  <label for="email">E-mail</label>
                  <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($colaborador['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label for="dt_nascimento">Data de Nascimento</label>
                  <input type="date" id="dt_nascimento" name="dt_nascimento" class="form-control" value="<?= htmlspecialchars($colaborador['dt_nascimento'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label for="nova_senha">Nova senha</label>
                  <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="Deixe em branco para manter a senha atual">
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                  <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
              </div>
            </div>
          </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
