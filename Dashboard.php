<?php
require 'db.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) { echo "<script>location.href='Login.php'</script>"; exit; }

$uid = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- 1. PROCESARE QUICK ADD (Păstrat) ---
if (isset($_POST['quick_add'])) {
    $title = trim($_POST['title']);
    $date = $_POST['due_date'] ?: null;
    $notes = trim($_POST['notes']);
    if($title) {
        $pdo->prepare("INSERT INTO Tasks (user_id, title, due_date, notes) VALUES (?, ?, ?, ?)")->execute([$uid, $title, $date, $notes]);
        echo "<script>location.href='Dashboard.php'</script>";
    }
}

// --- 2. INTEROGĂRI STATISTICI (Păstrat) ---
$stats = $pdo->prepare("SELECT COUNT(*) as tot, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as fin FROM Tasks WHERE user_id=? AND due_date=?");
$stats->execute([$uid, $today]);
$s = $stats->fetch();
$tTot = $s['tot']?:0; $tFin = $s['fin']?:0; $tRem = $tTot - $tFin;

$hTot = $pdo->prepare("SELECT COUNT(*) FROM Habits WHERE user_id=?")->execute([$uid]) ? $pdo->query("SELECT COUNT(*) FROM Habits WHERE user_id=$uid")->fetchColumn() : 0;
$hFin = $pdo->prepare("SELECT COUNT(DISTINCT habit_id) FROM Habit_Logs WHERE completed_date=? AND habit_id IN (SELECT habit_id FROM Habits WHERE user_id=?)");
$hFin->execute([$today, $uid]); 
$hDone = $hFin->fetchColumn(); $hRem = ($hTot - $hDone) > 0 ? ($hTot - $hDone) : 0;

// --- 3. INTEROGARE AGENDA (Păstrat) ---
$tasks = $pdo->prepare("SELECT * FROM Tasks WHERE user_id=? AND due_date=? ORDER BY status ASC");
$tasks->execute([$uid, $today]);
$habits = $pdo->prepare("SELECT h.habit_id, h.title, (SELECT COUNT(*) FROM Habit_Logs l WHERE l.habit_id=h.habit_id AND l.completed_date=?) as done FROM Habits h WHERE user_id=?");
$habits->execute([$today, $uid]);

// --- 4. LOGICĂ NOUĂ: PRELUARE INSIGNE ---
$myBadges = $pdo->prepare("SELECT badge_code, earned_at FROM User_Badges WHERE user_id=? ORDER BY earned_at DESC");
$myBadges->execute([$uid]);
$earnedBadges = $myBadges->fetchAll();

// Definim cum arată fiecare insignă (Configurare)
$badgeDef = [
    'first_task' => ['icon'=>'fa-check', 'color'=>'#3b82f6', 'name'=>'Început Bun', 'desc'=>'Primul task completat'],
    'task_master_10' => ['icon'=>'fa-list-check', 'color'=>'#8b5cf6', 'name'=>'Productiv', 'desc'=>'10 Task-uri completate'],
    'task_master_50' => ['icon'=>'fa-fire', 'color'=>'#ef4444', 'name'=>'Expert', 'desc'=>'50 Task-uri completate'],
    'first_journal' => ['icon'=>'fa-pen-nib', 'color'=>'#ec4899', 'name'=>'Dragă Jurnalule', 'desc'=>'Prima intrare scrisă'],
    'writer_5' => ['icon'=>'fa-book-open', 'color'=>'#f59e0b', 'name'=>'Scriitor', 'desc'=>'5 intrări în jurnal']
];
?>

<main class="container">
    <div style="margin-bottom:1.5rem;">
        <h2>Salut, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋</h2>
        <p class="text-muted">Iată o privire de ansamblu asupra productivității tale de azi.</p>
    </div>

    <div class="flex-cards" style="align-items: flex-start;">
        <section class="card" style="flex:1.5; min-width:320px;">
            <h3><i class="fa-solid fa-bolt" style="color:#f59e0b;"></i> Quick Action</h3>
            <form method="POST">
                <input type="hidden" name="quick_add" value="1">
                <label class="text-muted" style="font-size:0.85rem; font-weight:600;">Ce ai de făcut azi?</label>
                <input type="text" name="title" placeholder="Ex: Trimite raportul final..." required style="margin-top:0.5rem;">
                
                <div style="display:flex; gap:10px;">
                    <div style="flex:1">
                        <label class="text-muted" style="font-size:0.8rem;">Data Limită</label>
                        <input type="date" name="due_date" value="<?php echo $today; ?>">
                    </div>
                </div>
                <textarea name="notes" placeholder="Detalii opționale..." style="height:60px;"></textarea>
                
                <button class="btn" style="width:100%"><i class="fa-solid fa-plus"></i> Adaugă Task</button>
            </form>
        </section>

        <section class="card" style="flex:1; min-width:300px; text-align:center;">
            <h3><i class="fa-solid fa-chart-pie" style="color:var(--primary);"></i> Progres Azi</h3>
            <div style="display:flex; justify-content:space-around; align-items:center;">
                <div style="width:120px;">
                    <div class="chart-container" style="height:120px; width:120px;">
                        <canvas id="cTasks"></canvas>
                    </div>
                    <p style="margin-top:10px; font-weight:600;">Tasks</p>
                    <span class="text-muted"><?php echo "$tFin / $tTot"; ?></span>
                </div>
                <div style="border-left:1px solid var(--border); height:100px; margin:0 15px;"></div>
                <div style="width:120px;">
                    <div class="chart-container" style="height:120px; width:120px;">
                        <canvas id="cHabits"></canvas>
                    </div>
                    <p style="margin-top:10px; font-weight:600;">Habits</p>
                    <span class="text-muted"><?php echo "$hDone / $hTot"; ?></span>
                </div>
            </div>
        </section>
    </div>

    <section class="card" style="margin-top:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3><i class="fa-solid fa-trophy" style="color:#eab308;"></i> Trofeele Mele</h3>
            <?php if(!empty($earnedBadges)): ?>
                <span class="text-muted" style="font-size:0.85rem;"><?php echo count($earnedBadges); ?> insigne</span>
            <?php endif; ?>
        </div>

        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
            <?php foreach($earnedBadges as $b): 
                $code = $b['badge_code'];
                // Dacă insigna e definită în lista noastră, o afișăm
                if(isset($badgeDef[$code])): 
                    $info = $badgeDef[$code];
            ?>
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px 15px; display:flex; align-items:center; gap:12px; min-width:220px; flex:1;">
                    <div style="background:<?php echo $info['color']; ?>20; width:45px; height:45px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fa-solid <?php echo $info['icon']; ?>" style="color:<?php echo $info['color']; ?>; font-size:1.2rem;"></i>
                    </div>
                    <div>
                        <strong style="display:block; font-size:0.95rem; color:var(--text-main);"><?php echo $info['name']; ?></strong>
                        <small class="text-muted" style="font-size:0.8rem; line-height:1.2; display:block;"><?php echo $info['desc']; ?></small>
                    </div>
                </div>
            <?php endif; endforeach; ?>

            <?php if(empty($earnedBadges)): ?>
                <div style="padding:1rem; width:100%; text-align:center; color:var(--text-muted); background:#f9fafb; border-radius:8px;">
                    <i class="fa-regular fa-star" style="margin-bottom:5px; font-size:1.5rem;"></i>
                    <p style="margin:0;">Încă nu ai câștigat insigne. Completează task-uri și scrie în jurnal pentru a le debloca!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card" style="margin-top:1.5rem;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:1rem;">
            <h3><i class="fa-regular fa-calendar-check"></i> Agenda Zilei</h3>
            <span class="text-muted" style="font-size:0.9rem;">(<?php echo date('d M Y'); ?>)</span>
        </div>

        <div class="flex-cards">
            <div style="flex:1; min-width:300px;">
                <h4 style="color:var(--primary);">Sarcini</h4>
                <?php if($tTot==0): ?>
                    <div style="padding:1rem; background:#f9fafb; border-radius:8px; text-align:center; color:var(--text-muted);">
                        Nicio sarcină planificată azi.
                    </div>
                <?php endif; ?>
                
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <?php while($r=$tasks->fetch()): ?>
                    <div class="task-item" style="background:<?php echo $r['status']=='completed'?'#f0fdf4':'#fff'; ?>; border-radius:8px; border:1px solid <?php echo $r['status']=='completed'?'#bbf7d0':'#eee'; ?>;">
                        <input type="checkbox" class="custom-checkbox" 
                               <?php echo $r['status']=='completed'?'checked':''; ?> 
                               onclick="toggleStatus('task',<?php echo $r['task_id']; ?>,this)">
                        <div style="flex:1;">
                            <span class="<?php echo $r['status']=='completed'?'completed-text':''; ?>" style="font-weight:500;">
                                <?php echo htmlspecialchars($r['title']); ?>
                            </span>
                            <?php if($r['notes']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($r['notes']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            </div>

            <div style="flex:1; min-width:300px;">
                <h4 style="color:var(--success);">Obiceiuri</h4>
                <?php if($hTot==0): ?>
                    <div style="padding:1rem; background:#f9fafb; border-radius:8px; text-align:center; color:var(--text-muted);">
                        Nu ai setat obiceiuri.
                    </div>
                <?php endif; ?>

                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <?php while($h=$habits->fetch()): ?>
                    <div class="task-item" style="border-radius:8px; border:1px solid #eee;">
                        <input type="checkbox" class="custom-checkbox" 
                               <?php echo $h['done']?'checked':''; ?> 
                               onclick="toggleStatus('habit',<?php echo $h['habit_id']; ?>,this)">
                        <span class="<?php echo $h['done']?'completed-text':''; ?>" style="font-weight:500;">
                            <?php echo htmlspecialchars($h['title']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
// Chart Config
const commonOptions = { cutout: '70%', responsive: true, plugins: { legend: { display: false } } };
new Chart(document.getElementById('cTasks'), {
    type: 'doughnut',
    data: { labels:['Done','Todo'], datasets:[{ data:[<?php echo "$tFin,$tRem"; ?>], backgroundColor:['#4f46e5','#e5e7eb'], borderWidth:0 }] },
    options: commonOptions
});
new Chart(document.getElementById('cHabits'), {
    type: 'doughnut',
    data: { labels:['Done','Todo'], datasets:[{ data:[<?php echo "$hDone,$hRem"; ?>], backgroundColor:['#10b981','#e5e7eb'], borderWidth:0 }] },
    options: commonOptions
});
</script>
<?php include 'footer.php'; ?>