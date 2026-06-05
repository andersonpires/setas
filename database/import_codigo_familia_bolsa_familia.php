<?php
/**
 * Importação: codigo_familia
 * Origem: !Suporte/base_dados/bolsa_familia.csv - coluna CodFamilia
 * Tabela: codigo_familia (id_codigo_familia, codigo_familia)
 * Códigos duplicados são ignorados (apenas únicos).
 *
 * Execute: php import_codigo_familia_bolsa_familia.php
 * Ou acesse: http://localhost/setas-web/database/import_codigo_familia_bolsa_familia.php
 */

require_once dirname(__DIR__) . '/config/config.php';

set_time_limit(0);
ini_set('memory_limit', '256M');

$csvPath = BASE_PATH . '/!Suporte/base_dados/bolsa_familia.csv';

echo "<pre style='font-family:monospace;'>";
echo "=== Importação codigo_familia (Bolsa Família) ===\n\n";

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

    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `codigo_familia` (
          `id_codigo_familia` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `codigo_familia` varchar(50) NOT NULL,
          PRIMARY KEY (`id_codigo_familia`),
          UNIQUE KEY `uk_codigo_familia` (`codigo_familia`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "[OK] Tabela codigo_familia verificada/criada.\n\n";

    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        throw new Exception("Não foi possível abrir o arquivo CSV.");
    }

    $header = fgetcsv($handle, 0, ';');
    $codFamiliaIndex = array_search('CodFamilia', $header);
    if ($codFamiliaIndex === false) {
        fclose($handle);
        throw new Exception("Coluna 'CodFamilia' não encontrada no CSV.");
    }

    $codigosUnicos = [];
    $linha = 0;
    $duplicados = 0;

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $linha++;
        if (!isset($row[$codFamiliaIndex]) || trim($row[$codFamiliaIndex]) === '') {
            continue;
        }
        $codigo = trim($row[$codFamiliaIndex]);
        if (isset($codigosUnicos[$codigo])) {
            $duplicados++;
            continue;
        }
        $codigosUnicos[$codigo] = true;
    }
    fclose($handle);

    echo "Linhas processadas: " . $linha . "\n";
    echo "Códigos únicos encontrados: " . count($codigosUnicos) . "\n";
    echo "Duplicados ignorados no CSV: " . $duplicados . "\n\n";

    $codigos = array_keys($codigosUnicos);
    $totalAntes = (int) $pdo->query("SELECT COUNT(*) FROM codigo_familia")->fetchColumn();
    $batchSize = 500;

    for ($i = 0; $i < count($codigos); $i += $batchSize) {
        $batch = array_slice($codigos, $i, $batchSize);
        $placeholders = implode(',', array_fill(0, count($batch), '(?)'));
        $stmt = $pdo->prepare("INSERT IGNORE INTO codigo_familia (codigo_familia) VALUES " . $placeholders);
        $stmt->execute($batch);
    }

    $totalDepois = (int) $pdo->query("SELECT COUNT(*) FROM codigo_familia")->fetchColumn();
    $inseridos = $totalDepois - $totalAntes;
    $ignorados = count($codigos) - $inseridos;

    echo "[OK] Inseridos: " . $inseridos . "\n";
    echo "[OK] Já existiam (ignorados): " . $ignorados . "\n";
    echo "\n=== Importação concluída com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] Banco de dados: " . $e->getMessage() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
