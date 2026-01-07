<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['name'] : '';
// Determină pagina curentă pentru meniul activ
$currentData = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProChecker</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <?php else: ?>
                    <a href="Login.php" class="btn btn-sm" style="background:transparent; border:1px solid var(--primary); color:var(--primary); padding:0.4rem 1rem;">Login</a>
                    <a href="Register.php" class="btn btn-sm" style="margin-left:5px;">Register</a>
                <?php endif; ?>
            </nav>
            <?php if ($isLoggedIn): ?>
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="text-muted"><i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($userName); ?></span>
                <a href="Logout.php" style="color:var(--danger); font-size:1.2rem;" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
            </div>
            <?php endif; ?>
        </header>