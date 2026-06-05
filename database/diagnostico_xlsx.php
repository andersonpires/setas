<?php
/**
 * Diagnóstico para importação xlsx - vale_gas_ce
 * Execute e envie o resultado para identificar o problema.
 */
require_once dirname(__DIR__) . '/config/config.php';

$basePath = BASE_PATH . '/!Suporte/base_dados/';
$xlsxPath = $basePath . 'vale_gas_ce.xlsx';

header('Content-Type: text/plain; charset=utf-8');
echo "=== Diagnóstico vale_gas_ce.xlsx ===\n\n";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "Caminho xlsx: " . $xlsxPath . "\n";
echo "Arquivo existe: " . (file_exists($xlsxPath) ? 'SIM' : 'NAO') . "\n";
echo "Arquivo legível: " . (is_readable($xlsxPath) ? 'SIM' : 'NAO') . "\n";
echo "ZipArchive disponível: " . (class_exists('ZipArchive') ? 'SIM' : 'NAO') . "\n\n";

if (!file_exists($xlsxPath)) {
    echo "ERRO: Arquivo não encontrado. Verifique o caminho.\n";
    exit;
}

$zip = new ZipArchive();
$ok = $zip->open($xlsxPath, ZipArchive::RDONLY);
echo "Zip open(): " . ($ok === true ? 'OK' : "ERRO ($ok)") . "\n\n";

if ($ok !== true) {
    $bytes = @file_get_contents($xlsxPath, false, null, 0, 4);
    echo "Primeiros bytes (hex): " . bin2hex($bytes ?? '') . "\n";
    echo "(xlsx válido começa com 504B0304 = PK..)\n";
    exit;
}

echo "Arquivos dentro do zip:\n";
for ($i = 0; $i < min($zip->numFiles, 30); $i++) {
    echo "  - " . $zip->getNameIndex($i) . "\n";
}
if ($zip->numFiles > 30) {
    echo "  ... e mais " . ($zip->numFiles - 30) . " arquivos\n";
}

$sheetFound = null;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (stripos($name, 'sheet') !== false && stripos($name, '.xml') !== false) {
        $sheetFound = $name;
        break;
    }
}
echo "\nPrimeira planilha encontrada: " . ($sheetFound ?? 'NENHUMA') . "\n";

if ($sheetFound) {
    $xml = $zip->getFromName($sheetFound);
    echo "Tamanho XML: " . strlen($xml) . " bytes\n";
    echo "Contém sheetData: " . (strpos($xml, 'sheetData') !== false ? 'SIM' : 'NAO') . "\n";
    echo "Contém <row: " . substr_count($xml, '<row') . "\n";
}
$zip->close();
echo "\n=== Fim do diagnóstico ===\n";
