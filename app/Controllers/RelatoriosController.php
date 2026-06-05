<?php
/**
 * RelatoriosController - Relatórios do sistema
 */

class RelatoriosController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();

        $content = '<div class="card">
          <h4 style="margin-top:0;">Relatórios</h4>
          <p>Geração de relatórios em desenvolvimento.</p>
        </div>';

        $this->view('relatorios.index', [
            'pageTitle' => 'Relatórios',
            'currentPage' => 'relatorios',
            'content' => $content,
        ]);
    }
}
