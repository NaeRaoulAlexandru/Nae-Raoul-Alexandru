<?php
// Endpoint pentru înregistrare utilizator
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo 'Toate câmpurile sunt obligatorii.';
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Email invalid.';
    exit;
}

try {
    // Verificăm dacă email-ul există deja
    $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        http_response_code(409);
        echo 'Email-ul este deja înregistrat.';
        exit;
    }

    // Hash parola
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Inserăm utilizatorul
    $ins = $pdo->prepare('INSERT INTO Users (name, email, hashed_password) VALUES (?, ?, ?)');
    $ins->execute([$name, $email, $hashed]);

    // Redirecționare către pagina de login după succes
    header('Location: Login.html');
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Eroare server: ' . $e->getMessage();
    exit;
}
