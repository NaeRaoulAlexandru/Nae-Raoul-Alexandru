<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success'=>false]));

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$xpChange = 0;
$newBadge = null; // Variabilă pentru a anunța frontend-ul despre badge nou

// --- 1. PROCESARE TASK ---
if ($type === 'task') {
    $stmt = $pdo->prepare("SELECT status FROM Tasks WHERE task_id=? AND user_id=?");
    $stmt->execute([$id, $user_id]);
    if ($t = $stmt->fetch()) {
        $isCompleted = ($t['status'] == 'completed');
        $newStatus = $isCompleted ? 'pending' : 'completed';
        $xpChange = $isCompleted ? -10 : 10;
        
        $pdo->prepare("UPDATE Tasks SET status=? WHERE task_id=?")->execute([$newStatus, $id]);

        // --- BADGE CHECK (Doar la completare) ---
        if (!$isCompleted) { // Adică tocmai a devenit completed
            $count = $pdo->query("SELECT COUNT(*) FROM Tasks WHERE user_id=$user_id AND status='completed'")->fetchColumn();
            
            if ($count == 1) $newBadge = checkAndAward($user_id, 'first_task', $pdo);
            if ($count == 10) $newBadge = checkAndAward($user_id, 'task_master_10', $pdo);
            if ($count == 50) $newBadge = checkAndAward($user_id, 'task_master_50', $pdo);
        }
    }
} 
// --- 2. PROCESARE HABIT ---
elseif ($type === 'habit') {
    $check = $pdo->prepare("SELECT log_id FROM Habit_Logs WHERE habit_id=? AND completed_date=?");
    $check->execute([$id, $today]);
    if ($log = $check->fetch()) {
        $pdo->prepare("DELETE FROM Habit_Logs WHERE log_id=?")->execute([$log['log_id']]);
        $xpChange = -5;
    } else {
        $own = $pdo->prepare("SELECT habit_id FROM Habits WHERE habit_id=? AND user_id=?");
        $own->execute([$id, $user_id]);
        if($own->fetch()) {
            $pdo->prepare("INSERT INTO Habit_Logs (habit_id, completed_date) VALUES (?, ?)")->execute([$id, $today]);
            $xpChange = 5;
        }
    }
}

// --- 3. HELPER FUNCTION ---
function checkAndAward($uid, $code, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM User_Badges WHERE user_id=? AND badge_code=?");
    $stmt->execute([$uid, $code]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO User_Badges (user_id, badge_code) VALUES (?, ?)")->execute([$uid, $code]);
        return $code; // Returnăm codul pentru a afișa alertă
    }
    return null;
}

// --- 4. ACTUALIZARE XP ---
if ($xpChange != 0) {
    $uStmt = $pdo->prepare("SELECT xp, level FROM Users WHERE user_id=?");
    $uStmt->execute([$user_id]);
    $user = $uStmt->fetch();
    $newXP = max(0, $user['xp'] + $xpChange);
    $newLevel = floor($newXP / 100) + 1;
    $pdo->prepare("UPDATE Users SET xp=?, level=? WHERE user_id=?")->execute([$newXP, $newLevel, $user_id]);
    
    echo json_encode([
        'success' => true,
        'leveledUp' => ($newLevel > $user['level']),
        'newLevel' => $newLevel,
        'newBadge' => $newBadge // Trimitem info despre badge
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>