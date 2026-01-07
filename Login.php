<?php
require 'db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT user_id, name, hashed_password FROM Users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pass, $user['hashed_password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        header("Location: Dashboard.php");
        exit;
    } else {
        $error = "Email sau parolă incorectă.";
    }
}
include 'header.php';
?>

<main class="container" style="display:flex; justify-content:center; align-items:center; min-height:60vh;">
        <div class="card" style="width:100%; max-width:400px; padding:2rem;">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <i class="fa-solid fa-right-to-bracket" style="font-size:2rem; color:var(--primary);"></i>
            <h2>Bine ai revenit!</h2>
            <p class="text-muted">Introdu datele pentru a continua.</p>
        </div>

        <?php if($error): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:0.75rem; border-radius:8px; margin-bottom:1rem; text-align:center; font-size:0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:1rem;">
                <label style="font-weight:500; font-size:0.9rem;">Email</label>
                <input type="email" name="email" required placeholder="nume@exemplu.com" style="margin-top:5px;">
            </div>
            
            <div style="margin-bottom:1.5rem;">
                <label style="font-weight:500; font-size:0.9rem;">Parolă</label>
                <input type="password" name="password" required placeholder="••••••••" style="margin-top:5px;">
            </div>
            
            <button class="btn" style="width:100%; justify-content:center;">Autentificare</button>
        </form>
        
        <p class="text-muted" style="text-align:center; margin-top:1.5rem; font-size:0.9rem;">
            Nu ai cont? <a href="Register.php" style="color:var(--primary); font-weight:600; text-decoration:none;">Creează unul acum</a>
        </p>
    </div>
</main>

<?php include 'footer.php'; ?>