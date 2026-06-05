<?php
/**
 * Front Controller - Único ponto de entrada - SETAS-WEB
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Helpers/cpf.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/Controller.php';
require_once __DIR__ . '/app/Core/App.php';

session_start();

$app = new App();
