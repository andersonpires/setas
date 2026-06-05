<?php
/**
 * Preenche id_beneficiario na tabela cartao_mais_infancia
 * Atualiza apenas quando cpf, nome e nis forem idênticos nas duas tabelas.
 * Usa <=> para comparação NULL-safe (NULL = NULL retorna true).
 *
 * Execute: php preencher_id_beneficiario_cmi.php
 * Ou acesse: http://localhost/setas-web/database/preencher_id_beneficiario_cmi.php
 */

require_once dirname(__DIR__) . '/config/config.php';

echo "<pre style='font-family:monospace;'>";
echo "=== Preenchimento id_beneficiario em cartao_mais_infancia ===\n\n";

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");

    $sql = "
        UPDATE cartao_mais_infancia cmi
        INNER JOIN (
            SELECT cpf, nome, nis, MIN(id_beneficiario) AS id_beneficiario
            FROM beneficiario
            GROUP BY cpf, nome, nis
        ) b ON cmi.cpf = b.cpf
            AND (cmi.nome <=> b.nome)
            AND (cmi.nis <=> b.nis)
        SET cmi.id_beneficiario = b.id_beneficiario
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $atualizados = $stmt->rowCount();

    $depois = (int) $pdo->query("SELECT COUNT(*) FROM cartao_mais_infancia WHERE id_beneficiario IS NOT NULL")->fetchColumn();
    $semMatch = (int) $pdo->query("SELECT COUNT(*) FROM cartao_mais_infancia WHERE id_beneficiario IS NULL")->fetchColumn();

    echo "[OK] Registros atualizados (match cpf+nome+nis): " . $atualizados . "\n";
    echo "[OK] Total com id_beneficiario preenchido: " . $depois . "\n";
    echo "[OK] Total ainda sem match: " . $semMatch . "\n";
    echo "\n=== Concluído com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
