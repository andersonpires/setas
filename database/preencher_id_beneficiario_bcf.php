<?php
/**
 * Preenche id_beneficiario na tabela beneficiario_cod_familia
 * Atualiza apenas quando cpf, nome e nis forem idênticos nas duas tabelas.
 * Usa <=> para comparação NULL-safe (NULL = NULL retorna true).
 *
 * Execute: php preencher_id_beneficiario_bcf.php
 * Ou acesse: http://localhost/setas-web/database/preencher_id_beneficiario_bcf.php
 */

require_once dirname(__DIR__) . '/config/config.php';

echo "<pre style='font-family:monospace;'>";
echo "=== Preenchimento id_beneficiario em beneficiario_cod_familia ===\n\n";

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");

    $antes = (int) $pdo->query("SELECT COUNT(*) FROM beneficiario_cod_familia WHERE id_beneficiario IS NOT NULL")->fetchColumn();

    $sql = "
        UPDATE beneficiario_cod_familia bcf
        INNER JOIN (
            SELECT cpf, nome, nis, MIN(id_beneficiario) AS id_beneficiario
            FROM beneficiario
            GROUP BY cpf, nome, nis
        ) b ON bcf.cpf = b.cpf
            AND (bcf.nome <=> b.nome)
            AND (bcf.nis <=> b.nis)
        SET bcf.id_beneficiario = b.id_beneficiario
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $atualizados = $stmt->rowCount();

    $depois = (int) $pdo->query("SELECT COUNT(*) FROM beneficiario_cod_familia WHERE id_beneficiario IS NOT NULL")->fetchColumn();
    $semMatch = (int) $pdo->query("SELECT COUNT(*) FROM beneficiario_cod_familia WHERE id_beneficiario IS NULL")->fetchColumn();

    echo "[OK] Registros atualizados (match cpf+nome+nis): " . $atualizados . "\n";
    echo "[OK] Total com id_beneficiario preenchido: " . $depois . "\n";
    echo "[OK] Total ainda sem match: " . $semMatch . "\n";
    echo "\n=== Concluído com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
