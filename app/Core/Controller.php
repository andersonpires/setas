<?php
/**
 * Controller Base - SETAS-WEB
 */

abstract class Controller
{
    protected function estaLogado(): bool
    {
        if (!empty($_SESSION['colaborador_id'])) {
            return true;
        }
        $cookie = $_COOKIE['setas_remember'] ?? null;
        if ($cookie) {
            require_once BASE_PATH . '/app/Models/ColaboradorModel.php';
            $model = new ColaboradorModel();
            $colaborador = $model->validarTokenSessao($cookie);
            if ($colaborador) {
                $_SESSION['colaborador_id'] = $colaborador['id'];
                $_SESSION['colaborador_nome'] = $colaborador['nome'];
                $_SESSION['colaborador_permissao'] = $colaborador['permissao_nome'] ?? '';
                return true;
            }
        }
        return false;
    }

    protected function exigirLogin(): void
    {
        if (!$this->estaLogado()) {
            $this->redirect('auth/login');
            exit;
        }
    }

    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new RuntimeException("View não encontrada: {$view}");
        }
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        header('Location: ' . BASE_URL . ltrim($url, '/'), true, $statusCode);
        exit;
    }
}
