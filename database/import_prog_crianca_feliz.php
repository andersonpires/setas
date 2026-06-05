<?php
/**
 * Importação: prog_crianca_feliz (Programa Criança Feliz)
 * Origem: !Suporte/base_dados/prog_crianca_feliz.CSV
 * Colunas: id_prog_crianca_feliz (auto), cpf, nis, nome, cras, id_beneficiario (em branco)
 * CPF: remove "." e "-", apenas números (11 caracteres)
 *
 * Execute: php import_prog_crianca_feliz.php
 * Ou acesse: http://localhost/setas-web/database/import_prog_crianca_feliz.php
 */

require_once dirname(__DIR__) . '/config/config.php';

set_time_limit(0);
ini_set('memory_limit', '256M');

$basePath = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . '!Suporte' . DIRECTORY_SEPARATOR . 'base_dados' . DIRECTORY_SEPARATOR;
$csvPath = $basePath . 'prog_crianca_feliz.CSV';

echo "<pre style='font-family:monospace;'>";
echo "=== Importação prog_crianca_feliz ===\n\n";

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
    $pdo->exec("DROP TABLE IF EXISTS `prog_crianca_feliz`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->exec("
        CREATE TABLE `prog_crianca_feliz` (
          `id_prog_crianca_feliz` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `cpf` varchar(11) NOT NULL,
          `nis` varchar(20) DEFAULT NULL,
          `nome` varchar(255) DEFAULT NULL,
          `cras` varchar(100) DEFAULT NULL,
          `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
          PRIMARY KEY (`id_prog_crianca_feliz`),
          KEY `idx_cpf` (`cpf`),
          KEY `fk_pcf_beneficiario` (`id_beneficiario`),
          CONSTRAINT `fk_pcf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabela prog_crianca_feliz criada com nova estrutura.\n\n";

    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }

    $header = fgetcsv($handle, 0, ';');
    $idxCpf = obterColuna($header, ['cpf', 'CPF']);
    $idxNis = obterColuna($header, ['nis', 'NIS']);
    $idxNome = obterColuna($header, ['nome', 'NOME']);
    $idxCras = obterColuna($header, ['cras', 'CRAS']);

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
        $cras = ($idxCras !== false && isset($row[$idxCras]) && trim($row[$idxCras]) !== '') ? trim($row[$idxCras]) : null;

        $registros[] = [$cpf, $nis, $nome, $cras];
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
        $pdo->prepare("INSERT INTO prog_crianca_feliz (cpf, nis, nome, cras, id_beneficiario) VALUES " . $placeholders)->execute($params);
    }

    $total = (int) $pdo->query("SELECT COUNT(*) FROM prog_crianca_feliz")->fetchColumn();
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
