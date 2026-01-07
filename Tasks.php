<?php
require 'db.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) exit("<script>location.href='Login.php'</script>");

$uid = $_SESSION['user_id'];
if (isset($_POST['del_task'])) {
    $pdo->prepare("DELETE FROM Tasks WHERE task_id=? AND user_id=?")->execute([$_POST['tid'], $uid]);
}

$filter = $_GET['f'] ?? 'all';
$sql = "SELECT * FROM Tasks WHERE user_id=?";
if ($filter == 'day') $sql .= " AND due_date = CURDATE()";
if ($filter == 'week') $sql .= " AND YEARWEEK(due_date, 1) = YEARWEEK(CURDATE(), 1)";
if ($filter == 'month') $sql .= " AND MONTH(due_date) = MONTH(CURDATE()) AND YEAR(due_date) = YEAR(CURDATE())";
$sql .= " ORDER BY due_date ASC, status ASC";

$rows = $pdo->prepare($sql);
$rows->execute([$uid]);
$allTasks = $rows->fetchAll();
?>

<main class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
            <h2>Toate Sarcinile</h2>
            <p class="text-muted">Gestionează-ți lista completă de sarcini.</p>
        </div>
        <div class="filters">
            <a href="?f=all" class="<?php echo $filter=='all'?'active':''; ?>">Toate</a>
            <a href="?f=day" class="<?php echo $filter=='day'?'active':''; ?>">Azi</a>
            <a href="?f=week" class="<?php echo $filter=='week'?'active':''; ?>">Săptămână</a>
            <a href="?f=month" class="<?php echo $filter=='month'?'active':''; ?>">Lună</a>
        </div>
    </div>

    <div class="flex-cards">
        <section class="card" style="flex:1; min-width:350px;">
            <h3 style="color:var(--primary); border-bottom:2px solid #f3f4f6; padding-bottom:10px;">
                <i class="fa-regular fa-circle"></i> De Făcut
            </h3>
            
            <div style="display:flex; flex-direction:column;">
            <?php 
            $hasPending = false;
            foreach($allTasks as $r): if($r['status']=='completed') continue; $hasPending=true; ?>
                <div class="task-item">
                    <input type="checkbox" class="custom-checkbox" onclick="toggleStatus('task',<?php echo $r['task_id']; ?>,this)">
                    <div style="flex:1;">
                        <span style="font-weight:500; display:block;"><?php echo htmlspecialchars($r['title']); ?></span>
                        <div style="display:flex; gap:10px; font-size:0.8rem; margin-top:4px; color:var(--text-muted);">
                            <span><i class="fa-regular fa-calendar"></i> <?php echo $r['due_date']?:'Fără dată'; ?></span>
                            <?php if($r['notes']): ?><span><i class="fa-regular fa-sticky-note"></i> Note</span><?php endif; ?>
                        </div>
                    </div>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="tid" value="<?php echo $r['task_id']; ?>">
                        <button name="del_task" class="btn-icon" title="Șterge"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if(!$hasPending) echo "<p class='text-muted' style='text-align:center; padding:1rem;'>Totul e rezolvat! 🎉</p>"; ?>
            </div>
        </section>

        <section class="card" style="flex:1; min-width:350px; background:#f9fafb;">
            <h3 style="color:var(--success); border-bottom:2px solid #e5e7eb; padding-bottom:10px;">
                <i class="fa-solid fa-check-circle"></i> Completate
            </h3>
            
            <div style="display:flex; flex-direction:column;">
            <?php foreach($allTasks as $r): if($r['status']!='completed') continue; ?>
                <div class="task-item" style="opacity:0.75;">
                    <input type="checkbox" class="custom-checkbox" checked onclick="toggleStatus('task',<?php echo $r['task_id']; ?>,this)">
                    <div style="flex:1;">
                        <span class="completed-text" style="font-weight:500; display:block;"><?php echo htmlspecialchars($r['title']); ?></span>
                        <small class="text-muted"><?php echo $r['due_date']; ?></small>
                    </div>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="tid" value="<?php echo $r['task_id']; ?>">
                        <button name="del_task" class="btn-icon" title="Șterge"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
<?php include 'footer.php'; ?>