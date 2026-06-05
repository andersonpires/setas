<?php
/**
 * Script de Migração - SETAS-WEB
 * Execute este arquivo via navegador ou CLI para criar/atualizar tabelas no banco remoto.
 * Exemplo: php run_migrations.php ou acesse via http://localhost/setas-web/database/run_migrations.php
 */

require_once dirname(__DIR__) . '/config/config.php';

// Configurações do Banco
$host = DB_HOST;
$port = DB_PORT;
$dbname = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<pre style='font-family:monospace;'>";
    echo "=== SETAS-WEB - Migrations ===\n\n";

    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/migrations.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo migrations.sql não encontrado.");
    }

    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove comentários de linha
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove comentários de bloco

    // Executar cada statement (split por ; no final de linha/statement)
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        $stmt .= ';';
        try {
            $pdo->exec($stmt);
            echo "[OK] " . substr(str_replace(["\n", "\r"], ' ', $stmt), 0, 70) . "...\n";
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
                echo "[SKIP] " . substr($msg, 0, 80) . "\n";
            } else {
                throw $e;
            }
        }
    }

    // Inserir usuário Anderson Pires com hash correto
    $senhaHash = password_hash('300389', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO colaboradores (nome, cpf, email, senha, dt_nascimento, permissao_id, ativo)
        VALUES (:nome, :cpf, :email, :senha, :dt_nascimento, 1, 1)
        ON DUPLICATE KEY UPDATE 
            nome = VALUES(nome), 
            email = VALUES(email), 
            senha = VALUES(senha), 
            permissao_id = 1,
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        'nome' => 'Anderson Pires',
        'cpf' => '30038912864',
        'email' => 'andersonpires@msn.com',
        'senha' => $senhaHash,
        'dt_nascimento' => '1982-07-18',
    ]);
    echo "[OK] Usuário Anderson Pires inserido/atualizado.\n";

    echo "\n=== Migrations concluídas com sucesso! ===\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "<pre style='color:red;'>";
    echo "Erro de conexão/banco: " . $e->getMessage() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<pre style='color:red;'>";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "</pre>";
}
