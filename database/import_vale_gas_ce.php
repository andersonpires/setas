<?php
/**
 * Importação: vale_gas_ce
 * Origem: !Suporte/base_dados/vale_gas_ce.CSV
 * Colunas: id_vale_gas_ce (auto), cpf, nis, nome, situacao, id_beneficiario (em branco)
 * CPF: zeros ao final até 11 caracteres
 * NIS: NULL se vazio
 *
 * Execute: php import_vale_gas_ce.php
 * Ou acesse: http://localhost/setas-web/database/import_vale_gas_ce.php
 */

require_once dirname(__DIR__) . '/config/config.php';

set_time_limit(0);
ini_set('memory_limit', '256M');

$basePath = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . '!Suporte' . DIRECTORY_SEPARATOR . 'base_dados' . DIRECTORY_SEPARATOR;
$csvPath = $basePath . 'vale_gas_ce.CSV';

echo "<pre style='font-family:monospace;'>";
echo "=== Importação vale_gas_ce ===\n\n";

function normalizarCpf($cpf) {
    $cpf = preg_replace('/\D/', '', trim($cpf));
    return str_pad($cpf, 11, '0', STR_PAD_RIGHT);
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

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS `vale_gas_ce`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->exec("
        CREATE TABLE `vale_gas_ce` (
          `id_vale_gas_ce` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `cpf` varchar(11) NOT NULL,
          `nis` varchar(20) DEFAULT NULL,
          `nome` varchar(255) DEFAULT NULL,
          `situacao` varchar(50) DEFAULT NULL,
          `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
          PRIMARY KEY (`id_vale_gas_ce`),
          KEY `idx_cpf` (`cpf`),
          KEY `fk_vgce_beneficiario` (`id_beneficiario`),
          CONSTRAINT `fk_vgce_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabela vale_gas_ce criada com nova estrutura.\n\n";

    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }

    $header = fgetcsv($handle, 0, ';');
    $idxCpf = obterColuna($header, ['cpf', 'CPF']);
    $idxNis = obterColuna($header, ['nis', 'NIS']);
    $idxNome = obterColuna($header, ['nome', 'NOME']);
    $idxSituacao = obterColuna($header, ['situacao', 'SITUACAO', 'Situacao']);

    if ($idxCpf === false) {
        fclose($handle);
        throw new Exception("Coluna 'cpf' não encontrada no CSV.");
    }

    $registros = [];
    $linha = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $linha++;
        $cpf = normalizarCpf($row[$idxCpf] ?? '');
        $nis = ($idxNis !== false && isset($row[$idxNis]) && trim($row[$idxNis]) !== '') ? trim($row[$idxNis]) : null;
        $nome = ($idxNome !== false && isset($row[$idxNome]) && trim($row[$idxNome]) !== '') ? trim($row[$idxNome]) : null;
        $situacao = ($idxSituacao !== false && isset($row[$idxSituacao]) && trim($row[$idxSituacao]) !== '') ? trim($row[$idxSituacao]) : null;

        $registros[] = [$cpf, $nis, $nome, $situacao];
    }
    fclose($handle);

    echo "Linhas processadas: " . $linha . "\n";
    echo "Registros a importar: " . count($registros) . "\n\n";

    $batchSize = 500;
    for ($i = 0; $i < count($registros); $i += $batchSize) {
        $batch = array_slice($registros, $i, $batchSize);
        $placeholders = implode(',', array_fill(0, count($batch), '(?,?,?,?,NULL)'));
        $params = [];
        foreach ($batch as $r) {
            $params[] = $r[0];
            $params[] = $r[1];
            $params[] = $r[2];
            $params[] = $r[3];
        }
        $pdo->prepare("INSERT INTO vale_gas_ce (cpf, nis, nome, situacao, id_beneficiario) VALUES " . $placeholders)->execute($params);
    }

    $total = (int) $pdo->query("SELECT COUNT(*) FROM vale_gas_ce")->fetchColumn();
    echo "[OK] Total de registros na tabela: " . $total . "\n";
    echo "\n=== Importação concluída com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] Banco de dados: " . $e->getMessage() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
