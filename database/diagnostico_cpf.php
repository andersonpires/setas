<?php
/**
 * Diagnóstico: verifica CPF em CSVs e no banco de dados remoto
 * Uso: php diagnostico_cpf.php [CPF]
 * Ex: php diagnostico_cpf.php 63186086302
 */

require_once dirname(__DIR__) . '/config/config.php';

$cpfArg = $argv[1] ?? '63186086302';
$cpfDigitos = preg_replace('/\D/', '', $cpfArg);

echo "<pre style='font-family:monospace;'>";
echo "=== Diagnóstico CPF: " . $cpfArg . " ===\n\n";

$basePath = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . '!Suporte' . DIRECTORY_SEPARATOR . 'base_dados' . DIRECTORY_SEPARATOR;

// 1. Verificar nos CSVs
echo "--- ARQUIVOS CSV em !Suporte/base_dados ---\n\n";

$arquivos = [
    'bolsa_familia.csv' => ['sep' => ';', 'colCpf' => ['cpf', 'CPF']],
    'vale_gas_federal.CSV' => ['sep' => ';', 'colCpf' => ['CPF', 'cpf']],
    'vale_gas_ce.CSV' => ['sep' => ';', 'colCpf' => ['CPF', 'cpf']],
    'aluguel_social.CSV' => ['sep' => ';', 'colCpf' => ['CPF', 'cpf']],
    'cartao_ce_sem_fome.CSV' => ['sep' => ';', 'colCpf' => ['CPF', 'cpf']],
    'prog_crianca_feliz.CSV' => ['sep' => ';', 'colCpf' => ['CPF', 'cpf']],
    'cartao_mais_infancia.CSV' => ['sep' => ';', 'colCpf' => ['CPF', 'cpf']],
];

foreach ($arquivos as $arquivo => $config) {
    $path = $basePath . $arquivo;
    if (!file_exists($path)) {
        echo "[$arquivo] Arquivo não encontrado.\n";
        continue;
    }
    $handle = fopen($path, 'rb');
    if (!$handle) {
        echo "[$arquivo] Erro ao abrir.\n";
        continue;
    }
    $header = fgetcsv($handle, 0, $config['sep']);
    $idxCpf = false;
    foreach ($config['colCpf'] as $nome) {
        $idxCpf = array_search(trim($nome), array_map('trim', $header));
        if ($idxCpf !== false) break;
    }
    if ($idxCpf === false) {
        echo "[$arquivo] Coluna CPF não encontrada. Colunas: " . implode(', ', array_slice($header, 0, 5)) . "...\n";
        fclose($handle);
        continue;
    }
    $encontrados = 0;
    $linhas = [];
    $linha = 0;
    while (($row = fgetcsv($handle, 0, $config['sep'])) !== false) {
        $linha++;
        $cpfCelula = preg_replace('/\D/', '', (string)($row[$idxCpf] ?? ''));
        if (strlen($cpfCelula) < 5) continue;
        $match = ($cpfCelula === $cpfDigitos)
            || (strlen($cpfCelula) < 11 && str_pad($cpfCelula, 11, '0', STR_PAD_RIGHT) === $cpfDigitos)
            || (strlen($cpfCelula) < 11 && str_pad($cpfCelula, 11, '0', STR_PAD_LEFT) === $cpfDigitos);
        if ($match) {
            $encontrados++;
            $raw = $row[$idxCpf] ?? '';
            $nome = isset($row[4]) ? trim($row[4]) : (isset($row[3]) ? trim($row[3]) : '-');
            $linhas[] = "  Linha $linha: cpf_raw='$raw' (digits='$cpfCelula') | nome=$nome\n";
        }
    }
    fclose($handle);
    if ($encontrados > 0) {
        echo "[$arquivo] ENCONTRADO em $encontrados linha(s):\n";
        foreach (array_slice($linhas, 0, 5) as $l) echo $l . "\n";
        if (count($linhas) > 5) echo "  ... e mais " . (count($linhas) - 5) . " linha(s)\n";
    } else {
        echo "[$arquivo] Não encontrado.\n";
    }
}

// 2. Verificar no banco de dados
echo "\n--- BANCO DE DADOS REMOTO ---\n\n";

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");

    $normalizar = function($c) {
        $c = preg_replace('/\D/', '', $c);
        return str_pad($c, 11, '0', STR_PAD_RIGHT);
    };
    $cpfNorm = $normalizar($cpfArg);
    $cpfNormLeft = str_pad(preg_replace('/\D/', '', $cpfArg), 11, '0', STR_PAD_LEFT);

    echo "CPF normalizado (STR_PAD_RIGHT): $cpfNorm\n";
    echo "CPF normalizado (STR_PAD_LEFT):  $cpfNormLeft\n\n";

    // beneficiario
    $stmt = $pdo->prepare("SELECT id_beneficiario, cpf, nome FROM beneficiario WHERE cpf = ? OR cpf = ?");
    $stmt->execute([$cpfNorm, $cpfNormLeft]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "[beneficiario] " . (count($r) ? "ENCONTRADO: " . json_encode($r[0], JSON_UNESCAPED_UNICODE) : "Não encontrado") . "\n";

    $idBenef = $r[0]['id_beneficiario'] ?? null;

    if ($idBenef) {
        $tabelas = [
            'vale_gas_federal' => 'id_beneficiario',
            'vale_gas_ce' => 'id_beneficiario',
            'cartao_ce_sem_fome' => 'id_beneficiario',
            'prog_crianca_feliz' => 'id_beneficiario',
            'cartao_mais_infancia' => 'id_beneficiario',
        ];
        foreach ($tabelas as $tabela => $colId) {
            $stmt = $pdo->prepare("SELECT * FROM $tabela WHERE $colId = ? LIMIT 1");
            $stmt->execute([$idBenef]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "[$tabela] " . ($r ? "ENCONTRADO (id_beneficiario=$idBenef)" : "Não encontrado") . "\n";
        }
    }

    // aluguel_social - busca por CPF direto
    $stmt = $pdo->prepare("SELECT * FROM aluguel_social WHERE cpf = ? OR cpf = ? LIMIT 1");
    $stmt->execute([$cpfNorm, $cpfNormLeft]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "[aluguel_social] " . ($r ? "ENCONTRADO (por CPF)" : "Não encontrado") . "\n";

    // Verificar se existe em vale_gas_federal/cartao_mais_infancia por CPF bruto (caso tenha sido importado diferente)
    echo "\n--- Busca por variações de CPF no banco ---\n";
    $stmt = $pdo->query("SELECT id_beneficiario, cpf, nome FROM beneficiario WHERE cpf LIKE '%63186086302%' OR cpf LIKE '%63186086302' OR REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),' ','') = '63186086302'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Beneficiários com CPF relacionado a 63186086302: " . count($rows) . "\n";
    foreach ($rows as $ro) echo "  " . json_encode($ro, JSON_UNESCAPED_UNICODE) . "\n";

} catch (Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
}

echo "\n=== Fim do diagnóstico ===\n";
echo "</pre>";
