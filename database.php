<?php
// Use environment variables to store sensitive information
$host = getenv('DB_HOST') ?: 'localhost'; // Database host
$db   = getenv('DB_NAME') ?: 'rddb';      // Database name
$user = getenv('DB_USER') ?: 'kbitboy';   // Database user
$pass = getenv('DB_PASS') ?: 'danieyl';    // Database password
$charset = 'utf8mb4';                      // Charset for the database

// Data Source Name (DSN) for the PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for prepared statements
];

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log the error message to a file
    error_log("Database Connection Error: " . $e->getMessage(), 3, 'error_log.txt');
    
    // Display a user-friendly error message
    echo "Database connection failed! Please try again later.";
    exit; // Stop further execution
}
?>