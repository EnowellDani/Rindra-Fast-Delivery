<?php
// Use environment variables to store sensitive information
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'rddb';
$user = getenv('DB_USER') ?: 'kbitboy';
$pass = getenv('DB_PASS') ?: 'danieyl';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage(), 3, 'error_log.txt');
    echo "Database connection failed! Please try again later.";
}
?>