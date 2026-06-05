<?php
/**
 * BeneficiarioController - Consulta de beneficiário por CPF
 */

require_once BASE_PATH . '/app/Models/BeneficiarioModel.php';

class BeneficiarioController extends Controller
{
    public function index(): void
    {
        $this->exigirLogin();
        $this->view('beneficiario.index', [
            'pageTitle' => 'Beneficiário',
            'currentPage' => 'beneficiario',
            'subtitle' => 'Consulta de benefícios por CPF',
        ]);
    }

    public function buscar(): void
    {
        $this->exigirLogin();
        header('Content-Type: application/json; charset=utf-8');

        $cpf = trim($_POST['cpf'] ?? $_GET['cpf'] ?? '');
        if (empty($cpf)) {
            echo json_encode(['ok' => false, 'msg' => 'CPF não informado.']);
            return;
        }

        $cpfNumeros = preg_replace('/\D/', '', $cpf);
        if (!cpf_valido($cpfNumeros)) {
            echo json_encode(['ok' => false, 'msg' => 'O CPF digitado não é válido. Verifique se digitou corretamente ou digite um CPF válido.']);
            return;
        }

        $model = new BeneficiarioModel();
        $dados = $model->buscarPorCpf($cpf);

        if ($dados === null) {
            echo json_encode(['ok' => false, 'msg' => 'Nenhum dado encontrado para este CPF.']);
            return;
        }

        echo json_encode(['ok' => true, 'dados' => $dados]);
    }

    public function pdf(): void
    {
        $this->exigirLogin();
        ob_start();

        $cpf = trim($_GET['cpf'] ?? $_POST['cpf'] ?? '');
        $cpfNumeros = preg_replace('/\D/', '', $cpf);
        if (empty($cpfNumeros) || !cpf_valido($cpfNumeros)) {
            $_SESSION['flash_erro'] = 'CPF inválido ou não informado.';
            $this->redirect('beneficiario');
            return;
        }

        $model = new BeneficiarioModel();
        $dados = $model->buscarPorCpf($cpfNumeros);
        if ($dados === null) {
            $_SESSION['flash_erro'] = 'Nenhum dado encontrado para este CPF.';
            $this->redirect('beneficiario');
            return;
        }

        if (!defined('K_PATH_IMAGES')) {
            define('K_PATH_IMAGES', BASE_PATH . '/assets/logo_sistema/');
        }
        require_once BASE_PATH . '/app/Helpers/PdfBeneficiario.php';

        $pdf = new PdfBeneficiario('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('SETAS-WEB');
        $pdf->SetAuthor('SETAS-WEB');
        $pdf->SetTitle('Relatório de Beneficiário - ' . ($dados['nome'] ?? $cpfNumeros));

        $pdf->SetMargins(15, 28, 15);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->SetFont('dejavusans', '', 10);

        $logoPath = BASE_PATH . '/assets/logo_sistema/logo.png';
        $pdf->logoFile = (file_exists($logoPath) ? 'logo.png' : '');
        $pdf->logoWidth = 35;
        $pdf->setHeaderFont(['dejavusans', '', 9]);
        $pdf->setFooterFont(['dejavusans', '', 8]);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        $pdf->AddPage();

        function fmtData($d) {
            return $d ? date('d/m/Y', strtotime($d)) : '—';
        }
        function fmtMoeda($v) {
            if ($v === null || $v === '' || $v === false) return '—';
            return 'R$ ' . number_format((float)$v, 2, ',', '.');
        }
        function simNao($v) {
            return $v ? 'Sim' : 'Não';
        }

        $membros = !empty($dados['membros_familia']) ? implode(', ', $dados['membros_familia']) : '—';

        $html = '<style>
            h2 { color: rgb(63,159,110); font-size: 14pt; margin: 8px 0 6px; }
            .label { color: rgb(96,96,96); font-size: 8pt; }
            .valor { font-size: 10pt; margin-bottom: 6px; }
            .benef-item { margin-bottom: 4px; }
            .sim { color: rgb(63,159,110); font-weight: bold; }
            .nao { color: rgb(180,80,80); }
        </style>';

        $html .= '<h2>Dados do Beneficiário</h2>';
        $html .= '<div class="benef-item"><span class="label">CPF</span><br><span class="valor">' . htmlspecialchars($dados['cpf'] ?? '—') . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Nome</span><br><span class="valor">' . htmlspecialchars($dados['nome'] ?? '—') . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">NIS</span><br><span class="valor">' . htmlspecialchars($dados['nis'] ?? '—') . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Data de nascimento</span><br><span class="valor">' . fmtData($dados['dt_nascimento'] ?? null) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Sexo</span><br><span class="valor">' . htmlspecialchars($dados['sexo'] ?? '—') . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Endereço</span><br><span class="valor">' . htmlspecialchars($dados['endereco'] ?? '—') . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Renda familiar média</span><br><span class="valor">' . fmtMoeda($dados['renda_media'] ?? null) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Renda total</span><br><span class="valor">' . fmtMoeda($dados['renda_total'] ?? null) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Data do cadastro</span><br><span class="valor">' . fmtData($dados['data_cadastro'] ?? null) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Última atualização</span><br><span class="valor">' . fmtData($dados['data_atualizacao'] ?? null) . '</span></div>';

        $html .= '<h2>Código familiar e membros</h2>';
        $html .= '<div class="benef-item"><span class="label">Código familiar</span><br><span class="valor">' . htmlspecialchars($dados['codigo_familiar'] ?? '—') . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Outros membros da família</span><br><span class="valor">' . htmlspecialchars($membros) . '</span></div>';

        $html .= '<h2>Benefícios</h2>';
        $html .= '<div class="benef-item"><span class="label">Vale Gás Federal</span><br><span class="' . ($dados['possui_vale_gas_federal'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_vale_gas_federal'] ?? false) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Vale Gás Estadual</span><br><span class="' . ($dados['possui_vale_gas_estadual'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_vale_gas_estadual'] ?? false) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Aluguel Social</span><br><span class="' . ($dados['possui_aluguel_social'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_aluguel_social'] ?? false) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Cartão Ceará Sem Fome</span><br><span class="' . ($dados['possui_cartao_ce_sem_fome'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_cartao_ce_sem_fome'] ?? false) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Programa Criança Feliz</span><br><span class="' . ($dados['possui_prog_crianca_feliz'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_prog_crianca_feliz'] ?? false) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Cartão Mais Infância</span><br><span class="' . ($dados['possui_cartao_mais_infancia'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_cartao_mais_infancia'] ?? false) . '</span></div>';
        $html .= '<div class="benef-item"><span class="label">Bolsa Família</span><br><span class="' . ($dados['possui_bolsa_familia'] ? 'sim' : 'nao') . '">' . simNao($dados['possui_bolsa_familia'] ?? false) . '</span></div>';

        $codigoFamilia = trim($dados['codigo_familiar'] ?? '');
        if ($codigoFamilia !== '') {
            require_once BASE_PATH . '/app/Models/FamiliaModel.php';
            $familiaModel = new FamiliaModel();
            $membrosTabela = $familiaModel->buscarMembrosComBeneficios($codigoFamilia);
            if (!empty($membrosTabela)) {
                $html .= '<h2>Membros da Família</h2>';
                $html .= '<table border="1" cellpadding="4" cellspacing="0" style="font-size: 8pt; width: 100%; border-collapse: collapse;">';
                $html .= '<thead><tr style="background-color: rgb(63,159,110); color: white;">';
                $html .= '<th>CPF</th><th>Nome</th><th>V.Gás Fed.</th><th>V.Gás Est.</th><th>Aluguel Soc.</th>';
                $html .= '<th>Cartão CE Sem Fome</th><th>Prog. Criança Feliz</th><th>Cartão Mais Inf.</th><th>Bolsa Família</th>';
                $html .= '</tr></thead><tbody>';
                foreach ($membrosTabela as $m) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($m['cpf'] ?? '—') . '</td>';
                    $html .= '<td>' . htmlspecialchars($m['nome'] ?? '—') . '</td>';
                    $html .= '<td>' . simNao($m['possui_vale_gas_federal'] ?? false) . '</td>';
                    $html .= '<td>' . simNao($m['possui_vale_gas_estadual'] ?? false) . '</td>';
                    $html .= '<td>' . simNao($m['possui_aluguel_social'] ?? false) . '</td>';
                    $html .= '<td>' . simNao($m['possui_cartao_ce_sem_fome'] ?? false) . '</td>';
                    $html .= '<td>' . simNao($m['possui_prog_crianca_feliz'] ?? false) . '</td>';
                    $html .= '<td>' . simNao($m['possui_cartao_mais_infancia'] ?? false) . '</td>';
                    $html .= '<td>' . simNao($m['possui_bolsa_familia'] ?? false) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
            }
        }

        $dataEmissao = date('d/m/Y') . ' às ' . date('H') . 'h' . date('i');
        $html .= '<br><br><div style="text-align: center; font-size: 10pt;"><br>Relatório de Beneficiário<br>' . $dataEmissao . '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');
        ob_end_clean();
        $pdf->Output('relatorio_beneficiario_' . $cpfNumeros . '.pdf', 'I');
        exit;
    }
}
