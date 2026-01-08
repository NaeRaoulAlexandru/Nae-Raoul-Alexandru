<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Conectare la DB pentru a lua XP-ul actualizat
require_once 'db.php'; 

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['name'] : '';
$currentData = basename($_SERVER['PHP_SELF']);

// --- LOGICĂ GAMIFICATION ---
$userLevel = 1;
$userXP = 0;

if ($isLoggedIn) {
    // Luăm datele proaspete din DB (XP și Level)
    // Folosim un try-catch simplu pentru a evita erori fatale dacă tabelul nu e actualizat încă
    try {
        $stmt = $pdo->prepare("SELECT name, xp, level FROM Users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $uData = $stmt->fetch();
        if($uData) {
            $userName = $uData['name']; // Actualizăm numele (opțional)
            $userLevel = $uData['level'] ?? 1; // Default 1 dacă e null
            $userXP = $uData['xp'] ?? 0;       // Default 0 dacă e null
        }
    } catch (Exception $e) {
        // Ignorăm eroarea momentan dacă coloanele nu există
    }
}

// Calculăm procentul pentru bara de progres (restul împărțirii la 100)
// Ex: 145 XP => 45% plin
$progressPercent = $userXP % 100;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProChecker</title>
    
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="grid-home">
        <header>
            <h1><a href="Home.php"><i class="fa-solid fa-check-double"></i> ProChecker</a></h1>
            
            <nav>
                <a href="Home.php" class="<?php echo $currentData=='Home.php'?'active':''; ?>">Home</a>
                <?php if ($isLoggedIn): ?>
                    <a href="Dashboard.php" class="<?php echo $currentData=='Dashboard.php'?'active':''; ?>">Dashboard</a>
                    <a href="Tasks.php" class="<?php echo $currentData=='Tasks.php'?'active':''; ?>">Tasks</a>
                    <a href="Habits.php" class="<?php echo $currentData=='Habits.php'?'active':''; ?>">Habits</a>
                    <a href="Journal.php" class="<?php echo $currentData=='Journal.php'?'active':''; ?>">Journal</a>
                <?php else: ?>
                    <a href="Login.php" class="btn btn-sm" style="background:transparent; border:1px solid var(--primary); color:var(--primary); padding:0.4rem 1rem;">Login</a>
                    <a href="Register.php" class="btn btn-sm" style="margin-left:5px;">Register</a>
                <?php endif; ?>
            </nav>

            <?php if ($isLoggedIn): ?>
            <div style="display:flex; align-items:center; gap:15px;">
                
                <div class="level-badge" title="Total XP: <?php echo $userXP; ?>">
                    <div class="level-info">
                        <span class="lvl-num">LVL <?php echo $userLevel; ?></span>
                        <span class="xp-txt"><?php echo $progressPercent; ?>/100 XP</span>
                    </div>
                    <div class="xp-bar-bg">
                        <div class="xp-bar-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="text-muted"><i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($userName); ?></span>
                    <a href="Logout.php" style="color:var(--danger); font-size:1.2rem;" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
                </div>
            </div>
            <?php endif; ?>
        </header>