<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) exit(json_encode(['success'=>false]));

$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

if ($type === 'task') {
    $stmt = $pdo->prepare("SELECT status FROM Tasks WHERE task_id=? AND user_id=?");
    $stmt->execute([$id, $user_id]);
    if ($t = $stmt->fetch()) {
        $new = ($t['status'] == 'completed') ? 'pending' : 'completed';
        $pdo->prepare("UPDATE Tasks SET status=? WHERE task_id=?")->execute([$new, $id]);
        echo json_encode(['success'=>true]);
    }
} elseif ($type === 'habit') {
    $check = $pdo->prepare("SELECT log_id FROM Habit_Logs WHERE habit_id=? AND completed_date=?");
    $check->execute([$id, $today]);
    if ($log = $check->fetch()) {
        $pdo->prepare("DELETE FROM Habit_Logs WHERE log_id=?")->execute([$log['log_id']]);
    } else {
        $own = $pdo->prepare("SELECT habit_id FROM Habits WHERE habit_id=? AND user_id=?");
        $own->execute([$id, $user_id]);
        if($own->fetch()) {
            $pdo->prepare("INSERT INTO Habit_Logs (habit_id, completed_date) VALUES (?, ?)")->execute([$id, $today]);
        }
    }
    echo json_encode(['success'=>true]);
}
?>