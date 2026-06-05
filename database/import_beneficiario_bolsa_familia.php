<?php
/**
 * Importação: beneficiario
 * Origem: !Suporte/base_dados/bolsa_familia.csv
 * Tabela: beneficiario
 * - Não aceita mais de um registro com o mesmo CPF (exceto CPF = 0)
 * - CPF com menos de 11 dígitos: completa com zeros no final (ex: "0" -> "00000000000")
 *
 * Execute: php import_beneficiario_bolsa_familia.php
 * Ou acesse: http://localhost/setas-web/database/import_beneficiario_bolsa_familia.php
 */

require_once dirname(__DIR__) . '/config/config.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

$csvPath = BASE_PATH . '/!Suporte/base_dados/bolsa_familia.csv';

echo "<pre style='font-family:monospace;'>";
echo "=== Importação beneficiario (Bolsa Família) ===\n\n";

function normalizarCpf($cpf) {
    $cpf = preg_replace('/\D/', '', trim($cpf));
    return str_pad($cpf, 11, '0', STR_PAD_RIGHT);
}

function converterData($str) {
    if (empty(trim($str))) return null;
    $d = DateTime::createFromFormat('d/m/Y', trim($str));
    return $d ? $d->format('Y-m-d') : null;
}

function obterColuna($header, $nomes) {
    $header = array_map('trim', $header);
    foreach ((array) $nomes as $n) {
        $idx = array_search($n, $header);
        if ($idx !== false) return $idx;
    }
    return false;
}

try {
    if (!file_exists($csvPath)) {
        throw new Exception("Arquivo não encontrado: {$csvPath}");
    }

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `beneficiario` (
          `id_beneficiario` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `cpf` varchar(11) NOT NULL,
          `nis` varchar(20) DEFAULT NULL,
          `nome` varchar(255) DEFAULT NULL,
          `dt_nascimento` date DEFAULT NULL,
          `sexo` varchar(20) DEFAULT NULL,
          `tipo_logradouro` varchar(50) DEFAULT NULL,
          `logradouro` varchar(255) DEFAULT NULL,
          `localidade` varchar(150) DEFAULT NULL,
          `municipio` varchar(100) DEFAULT NULL,
          `renda_media` decimal(12,2) DEFAULT NULL,
          `renda_total` decimal(12,2) DEFAULT NULL,
          `ddd` varchar(5) DEFAULT NULL,
          `contato` varchar(20) DEFAULT NULL,
          `data_cadastro` date DEFAULT NULL,
          `data_atualizacao` date DEFAULT NULL,
          `pbf` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`id_beneficiario`),
          KEY `idx_beneficiario_cpf` (`cpf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $hasPbf = $pdo->query("SHOW COLUMNS FROM beneficiario LIKE 'pbf'")->fetch();
    if (!$hasPbf) {
        $pdo->exec("ALTER TABLE beneficiario ADD COLUMN pbf varchar(50) DEFAULT NULL AFTER data_atualizacao");
        echo "[OK] Coluna pbf adicionada.\n";
    }
    echo "[OK] Tabela beneficiario verificada/criada.\n\n";

    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }

    $header = fgetcsv($handle, 0, ';');
    $header = array_map('trim', $header);

    $cols = [
        'cpf'      => obterColuna($header, ['cpf', 'CPF']),
        'nis'      => obterColuna($header, ['nis', 'p.num_nis_pessoa_atual']),
        'nome'     => obterColuna($header, ['nome', 'p.nom_pessoa']),
        'dt_nasc'  => obterColuna($header, ['dt_nascimento', 'p.dta_nasc_pessoa']),
        'sexo'     => obterColuna($header, ['sexo', 'p.cod_sexo_pessoa']),
        'tipo_log' => obterColuna($header, ['tipo_logradouro', 'd.nom_tip_logradouro_fam']),
        'lograd'   => obterColuna($header, ['logradouro', 'd.nom_logradouro_fam']),
        'local'    => obterColuna($header, ['localidade', 'd.nom_localidade_fam']),
        'munic'    => obterColuna($header, ['municipio']),
        'renda_m'  => obterColuna($header, ['renda_media', 'd.vlr_renda_media_fam']),
        'renda_t'  => obterColuna($header, ['renda_total', 'd.vlr_renda_total_fam']),
        'ddd'      => obterColuna($header, ['ddd', 'd.num_ddd_contato_1_fam']),
        'contato'  => obterColuna($header, ['contato', 'd.num_tel_contato_1_fam']),
        'dat_cad'  => obterColuna($header, ['data_cadastro', 'd.dat_cadastramento_fam']),
        'dat_atual'=> obterColuna($header, ['data_atualizacao', 'd.dat_atual_fam']),
        'pbf'      => obterColuna($header, ['pbf', 'p.ref_pbf', 'd.ref_pbf']),
    ];

    if ($cols['cpf'] === false) {
        fclose($handle);
        throw new Exception("Coluna 'cpf' não encontrada no CSV.");
    }

    $seenCpf = [];
    $registros = [];
    $linha = 0;
    $duplicados = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $linha++;
        $cpf = normalizarCpf($row[$cols['cpf']] ?? '');
        if ($cpf !== '00000000000' && isset($seenCpf[$cpf])) {
            $duplicados++;
            continue;
        }
        $seenCpf[$cpf] = true;

        $pbfVal = ($cols['pbf'] !== false && isset($row[$cols['pbf']]) && trim($row[$cols['pbf']]) !== '') ? trim($row[$cols['pbf']]) : null;
        $registros[] = [
            $cpf,
            $cols['nis'] !== false && isset($row[$cols['nis']]) ? trim($row[$cols['nis']]) : null,
            $cols['nome'] !== false && isset($row[$cols['nome']]) ? trim($row[$cols['nome']]) : null,
            $cols['dt_nasc'] !== false && isset($row[$cols['dt_nasc']]) ? converterData($row[$cols['dt_nasc']]) : null,
            $cols['sexo'] !== false && isset($row[$cols['sexo']]) ? trim($row[$cols['sexo']]) : null,
            $cols['tipo_log'] !== false && isset($row[$cols['tipo_log']]) ? trim($row[$cols['tipo_log']]) : null,
            $cols['lograd'] !== false && isset($row[$cols['lograd']]) ? trim($row[$cols['lograd']]) : null,
            $cols['local'] !== false && isset($row[$cols['local']]) ? trim($row[$cols['local']]) : null,
            $cols['munic'] !== false && isset($row[$cols['munic']]) ? trim($row[$cols['munic']]) : null,
            $cols['renda_m'] !== false && isset($row[$cols['renda_m']]) && $row[$cols['renda_m']] !== '' ? (float) str_replace(',', '.', $row[$cols['renda_m']]) : null,
            $cols['renda_t'] !== false && isset($row[$cols['renda_t']]) && $row[$cols['renda_t']] !== '' ? (float) str_replace(',', '.', $row[$cols['renda_t']]) : null,
            $cols['ddd'] !== false && isset($row[$cols['ddd']]) ? trim($row[$cols['ddd']]) : null,
            $cols['contato'] !== false && isset($row[$cols['contato']]) ? trim($row[$cols['contato']]) : null,
            $cols['dat_cad'] !== false && isset($row[$cols['dat_cad']]) ? converterData($row[$cols['dat_cad']]) : null,
            $cols['dat_atual'] !== false && isset($row[$cols['dat_atual']]) ? converterData($row[$cols['dat_atual']]) : null,
            $pbfVal,
        ];
    }
    fclose($handle);

    echo "Linhas processadas: " . $linha . "\n";
    echo "Registros únicos a importar: " . count($registros) . "\n";
    echo "Duplicados ignorados (mesmo CPF): " . $duplicados . "\n\n";

    $batchSize = 500;
    $placeholders = '(' . implode(',', array_fill(0, 16, '?')) . ')';
    $colsSql = 'cpf, nis, nome, dt_nascimento, sexo, tipo_logradouro, logradouro, localidade, municipio, renda_media, renda_total, ddd, contato, data_cadastro, data_atualizacao, pbf';

    for ($i = 0; $i < count($registros); $i += $batchSize) {
        $batch = array_slice($registros, $i, $batchSize);
        $values = array_fill(0, count($batch), $placeholders);
        $sql = "INSERT INTO beneficiario ($colsSql) VALUES " . implode(',', $values);
        $params = array_merge(...$batch);
        $pdo->prepare($sql)->execute($params);
    }

    echo "[OK] Importados: " . count($registros) . " beneficiários.\n";
    echo "\n=== Importação concluída com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] Banco de dados: " . $e->getMessage() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
