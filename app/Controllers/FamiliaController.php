<?php
/**
 * FamiliaController - Página de membros da família com benefícios
 */

class FamiliaController extends Controller
{
    public function index(?string $codigo = null): void
    {
        $this->exigirLogin();

        $codigo = $codigo ?? ($_GET['codigo'] ?? '');
        $membros = [];
        $codigoFamilia = trim($codigo);

        if ($codigoFamilia !== '') {
            require_once BASE_PATH . '/app/Models/FamiliaModel.php';
            $model = new FamiliaModel();
            $membros = $model->buscarMembrosComBeneficios($codigoFamilia);
        }

        $this->view('familia.index', [
            'pageTitle' => 'Família',
            'currentPage' => 'familia',
            'codigoFamilia' => $codigoFamilia,
            'membros' => $membros,
        ]);
    }
}
