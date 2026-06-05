<?php
/**
 * HomeController - Dashboard
 */

require_once BASE_PATH . '/app/Models/ColaboradorModel.php';
require_once BASE_PATH . '/app/Models/BeneficiarioModel.php';
require_once BASE_PATH . '/app/Models/LoginModel.php';

class HomeController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();

        $colabModel = new ColaboradorModel();
        $benefModel = new BeneficiarioModel();
        $loginModel = new LoginModel();
        $totalColaboradores = $colabModel->countExcludingSuperadmin();
        $totalBeneficiarios = $benefModel->getTotalBeneficiariosDashboard();

        $ultimoAcesso = null;
        $colaboradorId = (int) ($_SESSION['colaborador_id'] ?? 0);
        if ($colaboradorId > 0) {
            $ultimoAcesso = $loginModel->buscarUltimoAcessoAnterior($colaboradorId);
        }

        $this->view('home.index', [
            'pageTitle' => 'Dashboard',
            'currentPage' => 'home',
            'colaboradorNome' => $_SESSION['colaborador_nome'] ?? 'Usuário',
            'ultimoAcesso' => $ultimoAcesso,
            'totalColaboradores' => $totalColaboradores,
            'totalBeneficiarios' => $totalBeneficiarios,
        ]);
    }
}
