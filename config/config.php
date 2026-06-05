<?php
/**
 * Configurações do Sistema SETAS-WEB
 */

define('BASE_PATH', dirname(__DIR__));

// Carregar variáveis de ambiente do .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value), '"\'');
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Ambiente e URL
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');
$baseUrl = getenv('BASE_URL') ?: 'http://localhost/setas-web/';
define('BASE_URL', rtrim($baseUrl, '/') . '/');

// Configurações do Banco de Dados
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8');

// Sessão
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 3600); // 1 hora
define('COOKIE_REMEMBER_DAYS', getenv('COOKIE_REMEMBER_DAYS') ?: 30);

// E-mail e recuperação de senha
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@setas-web.com.br');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'SETAS-WEB');
define('RECUP_SENHA_TOKEN_MINUTES', getenv('RECUP_SENHA_TOKEN_MINUTES') ?: 30);

// Chaves e API (ler de /temp/ se existir)
define('TEMP_PATH', BASE_PATH . '/temp');
