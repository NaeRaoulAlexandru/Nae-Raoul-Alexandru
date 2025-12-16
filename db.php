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
    http_response_code(500);
    echo "Eroare conectare DB: " . $e->getMessage();
    exit;
}