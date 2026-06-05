<?php
/**
 * TCPDF customizado para relatório de beneficiário
 * Cabeçalho: logo à esquerda, SETAS-WEB (com cores) à direita, grande
 */

require_once BASE_PATH . '/lib/tcpdf/tcpdf.php';

class PdfBeneficiario extends TCPDF
{
    public string $logoFile = '';
    public int $logoWidth = 22;

    public function Header(): void
    {
        $margins = $this->getMargins();
        $pageWidth = $this->getPageWidth();
        $left = $margins['left'] ?? 15;
        $right = $margins['right'] ?? 15;

        if ($this->logoFile !== '' && $this->logoWidth > 0) {
            $this->Image(
                K_PATH_IMAGES . $this->logoFile,
                $left,
                4,
                $this->logoWidth,
                0,
                'PNG',
                '',
                '',
                false,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                false
            );
        }

        $this->SetFont('dejavusans', 'B', 18);
        $w1 = $this->GetStringWidth('SETAS');
        $this->SetFont('dejavusans', '', 18);
        $w2 = $this->GetStringWidth('-WEB');
        $totalW = $w1 + $w2;
        $x = $pageWidth - $right - $totalW;
        $this->SetXY($x, 10);
        $this->SetFont('dejavusans', 'B', 18);
        $this->SetTextColorArray([63, 159, 110]);
        $this->Cell($w1, 8, 'SETAS', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $this->SetTextColorArray([15, 15, 15]);
        $this->SetFont('dejavusans', '', 18);
        $this->Cell($w2, 8, '-WEB', 0, 1, 'L', false, '', 0, false, 'T', 'M');

        $this->SetDrawColorArray([63, 159, 110]);
        $this->SetLineWidth(0.3);
        $this->Line($left, 20, $pageWidth - $right, 20);
    }

    public function Footer(): void
    {
        $this->SetFont('dejavusans', '', 8);
        $this->SetTextColorArray([128, 128, 128]);
        $lineWidth = 0.35;
        $this->SetLineStyle(['width' => $lineWidth, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [200, 200, 200]]);
        $this->SetY(-15);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 'T', 0, 'C', 0, '', 0, false, 'T', 'M');
    }
}
