<?php
/**
 * AuthController - Login, Logout, Lembrar senha
 */

require_once BASE_PATH . '/app/Models/ColaboradorModel.php';
require_once BASE_PATH . '/app/Models/LoginModel.php';
require_once BASE_PATH . '/app/Models/RecuperacaoSenhaModel.php';
require_once BASE_PATH . '/app/Services/MailService.php';

class AuthController extends Controller
{
    private ColaboradorModel $colaboradorModel;
    private RecuperacaoSenhaModel $recuperacaoSenhaModel;

    public function __construct()
    {
        $this->colaboradorModel = new ColaboradorModel();
        $this->recuperacaoSenhaModel = new RecuperacaoSenhaModel();
    }

    public function login(): void
    {
        if ($this->estaLogado()) {
            $this->redirect('home');
            return;
        }
        $this->view('auth.login', []);
    }

    public function autenticar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login');
            return;
        }

        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $manterLogado = !empty($_POST['manter_logado']);

        if (empty($cpf) || empty($senha)) {
            $this->view('auth.login', ['error' => 'CPF e senha são obrigatórios.']);
            return;
        }

        $colaborador = $this->colaboradorModel->buscarPorCpf($cpf);
        if (!$colaborador || !password_verify($senha, $colaborador['senha'])) {
            $this->view('auth.login', ['error' => 'CPF ou senha inválidos.']);
            return;
        }

        if (empty($colaborador['ativo'])) {
            $this->view('auth.login', ['error' => 'Usuário inativo. Entre em contato com o administrador.']);
            return;
        }

        $_SESSION['colaborador_id'] = $colaborador['id'];
        $_SESSION['colaborador_nome'] = $colaborador['nome'];
        $_SESSION['colaborador_permissao'] = $colaborador['permissao_nome'] ?? '';

        (new LoginModel())->registrar((int) $colaborador['id']);

        if ($manterLogado) {
            $this->criarCookieManterLogado($colaborador['id']);
        }

        $this->redirect('home');
    }

    public function logout(): void
    {
        $this->removerCookieManterLogado();
        session_destroy();
        session_start();
        $this->redirect('auth/login');
    }

    public function lembrarSenha(): void
    {
        $this->view('auth.lembrar_senha', ['etapa' => 1]);
    }

    public function recuperarSenha(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/lembrar-senha');
            return;
        }
        $token = trim($_POST['token'] ?? '');
        if ($token !== '') {
            $this->processarEtapaNovaSenha($token);
            return;
        }
        $this->processarEtapaValidacaoInicial();
    }

    private function processarEtapaValidacaoInicial(): void
    {
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $dtNasc = $_POST['dt_nascimento'] ?? '';

        if (empty($cpf) || empty($dtNasc)) {
            $this->view('auth.lembrar_senha', [
                'etapa' => 1,
                'erro' => 'CPF e data de nascimento são obrigatórios.',
            ]);
            return;
        }

        $colaborador = $this->colaboradorModel->buscarPorCpf($cpf);
        if (!$colaborador) {
            $this->view('auth.lembrar_senha', ['etapa' => 1, 'erro' => 'CPF não encontrado.']);
            return;
        }

        $dtCadastro = $colaborador['dt_nascimento'] ?? null;
        if (!$dtCadastro || $dtCadastro !== $dtNasc) {
            $this->view('auth.lembrar_senha', ['etapa' => 1, 'erro' => 'Data de nascimento não confere.']);
            return;
        }

        if (empty($colaborador['ativo'])) {
            $this->view('auth.lembrar_senha', ['etapa' => 1, 'erro' => 'Usuário inativo. Entre em contato com o administrador.']);
            return;
        }

        $token = $this->recuperacaoSenhaModel->gerarToken((int) $colaborador['id']);

        $this->view('auth.lembrar_senha', [
            'etapa' => 2,
            'token' => $token,
            'info' => 'Identidade confirmada. Cadastre sua nova senha para concluir o processo.',
        ]);
    }

    private function processarEtapaNovaSenha(string $tokenOriginal): void
    {
        $token = preg_replace('/[^a-f0-9]/i', '', $tokenOriginal);
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';

        if ($novaSenha !== $confirmar) {
            $this->view('auth.lembrar_senha', [
                'etapa' => 2,
                'erro' => 'As senhas não coincidem.',
                'token' => $token,
            ]);
            return;
        }
        if (strlen($novaSenha) < 6) {
            $this->view('auth.lembrar_senha', [
                'etapa' => 2,
                'erro' => 'Senha deve ter no mínimo 6 caracteres.',
                'token' => $token,
            ]);
            return;
        }

        if (empty($token)) {
            $this->view('auth.lembrar_senha', [
                'etapa' => 1,
                'erro' => 'Token inválido. Inicie novamente o processo de recuperação.',
            ]);
            return;
        }

        $registro = $this->recuperacaoSenhaModel->buscarTokenValido($token);
        if (!$registro) {
            $this->view('auth.lembrar_senha', [
                'etapa' => 1,
                'erro' => 'Token expirado ou inválido. Refaça a validação de CPF e data de nascimento.',
            ]);
            return;
        }

        $colaborador = $this->colaboradorModel->buscarPorId((int) $registro['colaborador_id']);
        if (!$colaborador) {
            $this->view('auth.lembrar_senha', [
                'etapa' => 1,
                'erro' => 'Colaborador não encontrado. Refaça o processo.',
            ]);
            return;
        }

        $this->colaboradorModel->atualizarSenha((int) $colaborador['id'], $novaSenha);
        $this->recuperacaoSenhaModel->marcarComoUsado((int) $registro['id']);

        $this->enviarEmailConfirmacao($colaborador['email'] ?? '', $colaborador['nome'] ?? '');

        $emailMascarado = $this->mascararEmail($colaborador['email'] ?? '');
        $mensagem = sprintf(
            'A recuperação de senha ocorreu com sucesso. Um e-mail foi enviado para %s.',
            $emailMascarado
        );

        $this->view('auth.lembrar_senha', [
            'etapa' => 3,
            'sucesso' => $mensagem,
            'email_mascarado' => $emailMascarado,
        ]);
    }

    private function mascararEmail(string $email): string
    {
        if (strpos($email, '@') === false) {
            return $email;
        }
        [$local, $dominio] = explode('@', $email, 2);
        $localMasc = $this->mascararSegmento($local);
        $dominioMasc = $this->mascararDominio($dominio);
        return $localMasc . '@' . $dominioMasc;
    }

    private function mascararSegmento(string $texto): string
    {
        $length = strlen($texto);
        $visiveis = min(2, $length);
        $prefixo = substr($texto, 0, $visiveis);
        $mascara = str_repeat('*', max(0, $length - $visiveis));
        return $prefixo . $mascara;
    }

    private function mascararDominio(string $dominio): string
    {
        $posPonto = strpos($dominio, '.');
        if ($posPonto === false) {
            return $this->mascararSegmento($dominio);
        }
        $inicio = substr($dominio, 0, $posPonto);
        $restante = substr($dominio, $posPonto);
        $inicioMasc = $this->mascararSegmento($inicio);
        return $inicioMasc . $restante;
    }

    private function enviarEmailConfirmacao(string $destinatario, string $nome): void
    {
        if (empty($destinatario)) {
            return;
        }

        $nomeSeguro = htmlspecialchars($nome ?: 'Colaborador', ENT_QUOTES, 'UTF-8');
        $dataHora = date('d/m/Y H:i');
        $loginUrl = BASE_URL . 'auth/login';

        $html = '
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Recuperação de senha concluída</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f6fa; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
                .header { background: linear-gradient(135deg, #0f172a, #1d4ed8); padding: 32px; color: #ffffff; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; letter-spacing: 1px; }
                .content { padding: 32px; color: #1f2937; line-height: 1.6; }
                .content h2 { color: #0f172a; margin-top: 0; }
                .cta { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #1d4ed8; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; }
                .footer { padding: 16px 32px; background: #f8fafc; color: #64748b; font-size: 12px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>SETAS-WEB</h1>
                    <p>Recuperação de senha concluída</p>
                </div>
                <div class="content">
                    <h2>Olá, ' . $nomeSeguro . '!</h2>
                    <p>Confirmamos que sua senha no SETAS-WEB foi atualizada com sucesso em ' . $dataHora . '.</p>
                    <p>Se você realizou esta alteração, nenhuma ação adicional é necessária. Caso não tenha sido você, altere a senha novamente e informe imediatamente a equipe de suporte.</p>
                    <p>Você já pode acessar o sistema utilizando a nova senha cadastrada:</p>
                    <p style="text-align: center;">
                        <a class="cta" href="' . $loginUrl . '">Acessar o SETAS-WEB</a>
                    </p>
                </div>
                <div class="footer">
                    © ' . date('Y') . ' SETAS-WEB · Secretaria do Trabalho e Assistência Social
                </div>
            </div>
        </body>
        </html>';

        $enviado = MailService::sendHtml($destinatario, 'SETAS-WEB - Recuperação de senha concluída', $html);
        if (!$enviado) {
            error_log('[SETAS-WEB] Falha ao enviar e-mail de confirmação de senha para ' . $destinatario);
        }
    }

    private function criarCookieManterLogado(int $colaboradorId): void
    {
        $token = bin2hex(random_bytes(32));
        $expira = time() + (COOKIE_REMEMBER_DAYS * 86400);
        $this->colaboradorModel->salvarTokenSessao($colaboradorId, $token, $expira);
        setcookie('setas_remember', $token, $expira, '/');
    }

    private function removerCookieManterLogado(): void
    {
        if (isset($_COOKIE['setas_remember'])) {
            $this->colaboradorModel->removerTokenSessao($_COOKIE['setas_remember']);
            setcookie('setas_remember', '', time() - 3600, '/');
        }
    }
}
