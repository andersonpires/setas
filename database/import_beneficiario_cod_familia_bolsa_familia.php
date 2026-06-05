<?php
/**
 * Importação: beneficiario_cod_familia
 * Origem: !Suporte/base_dados/bolsa_familia.csv
 * Colunas: id_membro_familia (auto), codigo_familia (CodFamilia), cpf (cpf, 11 chars), nome, nis, id_beneficiario (em branco)
 * CPF: zeros ao final até 11 caracteres
 * codigo_familia pode repetir (relação 1:N). Importa TODOS os registros do CSV (sem deduplicação).
 *
 * Pré-requisito: executar import_codigo_familia e import_beneficiario antes
 *
 * Execute: php import_beneficiario_cod_familia_bolsa_familia.php
 * Ou acesse: http://localhost/setas-web/database/import_beneficiario_cod_familia_bolsa_familia.php
 */

require_once dirname(__DIR__) . '/config/config.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

$csvPath = BASE_PATH . '/!Suporte/base_dados/bolsa_familia.csv';

echo "<pre style='font-family:monospace;'>";
echo "=== Importação beneficiario_cod_familia (Bolsa Família) ===\n\n";

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
    $pdo->exec("DROP TABLE IF EXISTS `beneficiario_cod_familia`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->exec("
        CREATE TABLE `beneficiario_cod_familia` (
          `id_membro_familia` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `codigo_familia` varchar(50) NOT NULL,
          `cpf` varchar(11) NOT NULL,
          `nome` varchar(255) DEFAULT NULL,
          `nis` varchar(20) DEFAULT NULL,
          `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
          PRIMARY KEY (`id_membro_familia`),
          KEY `idx_codigo_familia` (`codigo_familia`),
          KEY `idx_cpf` (`cpf`),
          KEY `fk_bcf_beneficiario` (`id_beneficiario`),
          CONSTRAINT `fk_bcf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabela beneficiario_cod_familia criada com nova estrutura.\n\n";

    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }

    $header = fgetcsv($handle, 0, ';');
    $idxCpf = obterColuna($header, ['cpf', 'CPF']);
    $idxCodFamilia = obterColuna($header, ['CodFamilia']);
    $idxNome = obterColuna($header, ['nome', 'p.nom_pessoa']);
    $idxNis = obterColuna($header, ['nis', 'p.num_nis_pessoa_atual']);

    if ($idxCpf === false || $idxCodFamilia === false) {
        fclose($handle);
        throw new Exception("Colunas 'cpf' e/ou 'CodFamilia' não encontradas no CSV.");
    }

    $registros = [];
    $linha = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $linha++;
        $cpf = normalizarCpf($row[$idxCpf] ?? '');
        $codFamilia = trim($row[$idxCodFamilia] ?? '');
        if ($codFamilia === '') continue;

        $nome = ($idxNome !== false && isset($row[$idxNome]) && trim($row[$idxNome]) !== '') ? trim($row[$idxNome]) : null;
        $nis = ($idxNis !== false && isset($row[$idxNis]) && trim($row[$idxNis]) !== '') ? trim($row[$idxNis]) : null;

        $registros[] = [$codFamilia, $cpf, $nome, $nis];
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
        $pdo->prepare("INSERT INTO beneficiario_cod_familia (codigo_familia, cpf, nome, nis, id_beneficiario) VALUES " . $placeholders)->execute($params);
    }

    $total = (int) $pdo->query("SELECT COUNT(*) FROM beneficiario_cod_familia")->fetchColumn();
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
