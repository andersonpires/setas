<?php
/**
 * Importação: cartao_ce_sem_fome (Cartão CE Sem Fome)
 * Origem: !Suporte/base_dados/cartao_ce_sem_fome.CSV
 * Colunas: id_cartao_ce_sem_fome (auto), cpf, nome, situacao, id_beneficiario (em branco)
 * CPF: remove "." e "-", apenas números (11 caracteres)
 * situacao: preserva acentuação (UTF-8)
 *
 * Execute: php import_cartao_ce_sem_fome.php
 * Ou acesse: http://localhost/setas-web/database/import_cartao_ce_sem_fome.php
 */

require_once dirname(__DIR__) . '/config/config.php';

set_time_limit(0);
ini_set('memory_limit', '256M');

$basePath = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . '!Suporte' . DIRECTORY_SEPARATOR . 'base_dados' . DIRECTORY_SEPARATOR;
$csvPath = $basePath . 'cartao_ce_sem_fome.CSV';

echo "<pre style='font-family:monospace;'>";
echo "=== Importação cartao_ce_sem_fome ===\n\n";

function garantirUtf8($str) {
    if ($str === '' || $str === null) return $str;
    if (mb_check_encoding($str, 'UTF-8')) return $str;
    foreach (['Windows-1252', 'ISO-8859-1', 'CP1252'] as $enc) {
        $utf8 = @mb_convert_encoding($str, 'UTF-8', $enc);
        if ($utf8 !== false && mb_check_encoding($utf8, 'UTF-8')) return $utf8;
    }
    return $str;
}

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
    foreach ((array) $nomes as $n) {
        foreach ($header as $i => $h) {
            $hNorm = preg_replace('/[^a-z0-9]/iu', '', $h);
            $nNorm = preg_replace('/[^a-z0-9]/iu', '', $n);
            if ($hNorm !== '' && $nNorm !== '' && stripos($hNorm, $nNorm) !== false) return $i;
        }
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
    $pdo->exec("SET NAMES utf8mb4");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS `cartao_ce_sem_fome`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->exec("
        CREATE TABLE `cartao_ce_sem_fome` (
          `id_cartao_ce_sem_fome` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `cpf` varchar(11) NOT NULL,
          `nome` varchar(255) DEFAULT NULL,
          `situacao` varchar(100) DEFAULT NULL,
          `id_beneficiario` int(11) UNSIGNED DEFAULT NULL,
          PRIMARY KEY (`id_cartao_ce_sem_fome`),
          KEY `idx_cpf` (`cpf`),
          KEY `fk_ccsf_beneficiario` (`id_beneficiario`),
          CONSTRAINT `fk_ccsf_beneficiario` FOREIGN KEY (`id_beneficiario`) REFERENCES `beneficiario` (`id_beneficiario`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabela cartao_ce_sem_fome criada com nova estrutura.\n\n";

    $handle = fopen($csvPath, 'rb');
    if (!$handle) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $header = fgetcsv($handle, 0, ';');
    $header = $header ? array_map('garantirUtf8', array_map('trim', $header)) : [];
    $idxCpf = obterColuna($header, ['cpf', 'CPF']);
    $idxNome = obterColuna($header, ['nome', 'NOME']);
    $idxSituacao = obterColuna($header, ['situacao', 'Situação', 'Situacao', 'Status']);

    if ($idxCpf === false) {
        fclose($handle);
        throw new Exception("Coluna 'cpf' não encontrada no CSV.");
    }

    $registros = [];
    $linha = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $linha++;
        $cpf = normalizarCpf($row[$idxCpf] ?? '');
        $nome = ($idxNome !== false && isset($row[$idxNome]) && trim($row[$idxNome]) !== '') ? garantirUtf8(trim($row[$idxNome])) : null;
        $situacao = ($idxSituacao !== false && isset($row[$idxSituacao]) && trim($row[$idxSituacao]) !== '') ? garantirUtf8(trim($row[$idxSituacao])) : null;

        $registros[] = [$cpf, $nome, $situacao];
    }
    fclose($handle);

    echo "Linhas processadas: " . $linha . "\n";
    echo "Registros a importar: " . count($registros) . "\n\n";

    $batchSize = 500;
    for ($i = 0; $i < count($registros); $i += $batchSize) {
        $batch = array_slice($registros, $i, $batchSize);
        $placeholders = implode(',', array_fill(0, count($batch), '(?,?,?,NULL)'));
        $params = [];
        foreach ($batch as $r) {
            $params[] = $r[0];
            $params[] = $r[1];
            $params[] = $r[2];
        }
        $pdo->prepare("INSERT INTO cartao_ce_sem_fome (cpf, nome, situacao, id_beneficiario) VALUES " . $placeholders)->execute($params);
    }

    $total = (int) $pdo->query("SELECT COUNT(*) FROM cartao_ce_sem_fome")->fetchColumn();
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
