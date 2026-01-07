<?php
// Config conexiune — conform docker-compose.yml

// Numele serviciului MySQL în docker-compose (NU localhost)
$host = 'mysql';
$port = 3306;
$db = 'studenti';
$user = 'user';           // din MYSQL_USER
$pass = 'password';       // din MYSQL_PASSWORD
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Fallback: try localhost (useful if not running inside Docker)
    try {
        $dsn2 = "mysql:host=127.0.0.1;port=$port;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn2, $user, $pass, $options);
    } catch (PDOException $e2) {
        http_response_code(500);
        echo "Eroare conectare DB: " . $e2->getMessage();
        exit;
    }
}