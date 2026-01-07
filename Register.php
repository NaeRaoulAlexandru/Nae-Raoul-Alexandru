<?php
require 'db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email=?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Acest email este deja utilizat.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO Users (name, email, hashed_password) VALUES (?, ?, ?)");
        if ($ins->execute([$name, $email, $hash])) {
            header("Location: Login.php");
            exit;
        } else {
            $error = "A apărut o eroare. Încearcă din nou.";
        }
    }
}
include 'header.php';
?>

<main class="container" style="display:flex; justify-content:center; align-items:center; min-height:60vh;">    <div class="card" style="width:100%; max-width:400px; padding:2rem;">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <i class="fa-solid fa-user-plus" style="font-size:2rem; color:var(--primary);"></i>
            <h2>Creează Cont</h2>
            <p class="text-muted">Alătură-te comunității ProChecker.</p>
        </div>

        <?php if($error): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:0.75rem; border-radius:8px; margin-bottom:1rem; text-align:center; font-size:0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:1rem;">
                <label style="font-weight:500; font-size:0.9rem;">Nume Complet</label>
                <input type="text" name="name" required placeholder="Ion Popescu" style="margin-top:5px;">
            </div>

            <div style="margin-bottom:1rem;">
                <label style="font-weight:500; font-size:0.9rem;">Email</label>
                <input type="email" name="email" required placeholder="nume@exemplu.com" style="margin-top:5px;">
            </div>
            
            <div style="margin-bottom:1.5rem;">
                <label style="font-weight:500; font-size:0.9rem;">Parolă</label>
                <input type="password" name="password" minlength="6" required placeholder="Minim 6 caractere" style="margin-top:5px;">
            </div>
            
            <button class="btn" style="width:100%; justify-content:center;">Înregistrare</button>
        </form>
        
        <p class="text-muted" style="text-align:center; margin-top:1.5rem; font-size:0.9rem;">
            Ai deja cont? <a href="Login.php" style="color:var(--primary); font-weight:600; text-decoration:none;">Loghează-te</a>
        </p>
    </div>
</main>

<?php include 'footer.php'; ?>