<?php
/**
 * Preenche id_beneficiario na tabela cartao_ce_sem_fome
 * Atualiza apenas quando cpf e nome forem idênticos nas duas tabelas.
 *
 * Execute: php preencher_id_beneficiario_ccsf.php
 * Ou acesse: http://localhost/setas-web/database/preencher_id_beneficiario_ccsf.php
 */

require_once dirname(__DIR__) . '/config/config.php';

echo "<pre style='font-family:monospace;'>";
echo "=== Preenchimento id_beneficiario em cartao_ce_sem_fome ===\n\n";

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");

    $sql = "
        UPDATE cartao_ce_sem_fome ccsf
        INNER JOIN (
            SELECT cpf, nome, MIN(id_beneficiario) AS id_beneficiario
            FROM beneficiario
            GROUP BY cpf, nome
        ) b ON ccsf.cpf = b.cpf
            AND (ccsf.nome <=> b.nome)
        SET ccsf.id_beneficiario = b.id_beneficiario
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $atualizados = $stmt->rowCount();

    $depois = (int) $pdo->query("SELECT COUNT(*) FROM cartao_ce_sem_fome WHERE id_beneficiario IS NOT NULL")->fetchColumn();
    $semMatch = (int) $pdo->query("SELECT COUNT(*) FROM cartao_ce_sem_fome WHERE id_beneficiario IS NULL")->fetchColumn();

    echo "[OK] Registros atualizados (match cpf+nome): " . $atualizados . "\n";
    echo "[OK] Total com id_beneficiario preenchido: " . $depois . "\n";
    echo "[OK] Total ainda sem match: " . $semMatch . "\n";
    echo "\n=== Concluído com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
