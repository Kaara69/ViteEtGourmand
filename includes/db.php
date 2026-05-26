<?php
// Include de la configuration (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
require_once dirname(__DIR__) . '/config.php';


// Construction du DSN (Data Source Name) pour MySQL
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
);

// Options PDO recommandées
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Création de la connexion PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    
    die('Erreur MySQL : ' . $e->getMessage());
}