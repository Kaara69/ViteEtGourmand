<?php

$is_local = str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost');


if ($is_local) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'vite_gourmand');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('BASE_URL', '/');

    define('MONGO_HOST', getenv('MONGO_HOST') ?: 'localhost');
    define('MONGO_PORT', getenv('MONGO_PORT') ?: '27017');
    define('MONGO_DB',   getenv('MONGO_DB')   ?: 'vite_gourmand');
}

require_once __DIR__ . '/vendor/autoload.php';

// Charge les variables du fichier .env
$env = parse_ini_file(__DIR__ . '/.env');
define('MAIL_USER', $env['MAIL_USER'] ?? '');
define('MAIL_PASS', $env['MAIL_PASS'] ?? '');

error_log("MAIL_USER = " . ($env['MAIL_USER'] ?? 'VIDE'));
error_log("MAIL_PASS = " . ($env['MAIL_PASS'] ?? 'VIDE'));