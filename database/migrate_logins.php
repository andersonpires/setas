<?php
/**
 * Migração da tabela logins
 * Execute: php database/migrate_logins.php ou acesse via http://localhost/setas-web/database/migrate_logins.php
 */

require_once dirname(__DIR__) . '/config/config.php';

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

    $sql = "CREATE TABLE IF NOT EXISTS `logins` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `colaborador_id` int(11) UNSIGNED NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logins_colaborador` (`colaborador_id`),
  CONSTRAINT `fk_logins_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "Tabela logins criada/verificada com sucesso.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
