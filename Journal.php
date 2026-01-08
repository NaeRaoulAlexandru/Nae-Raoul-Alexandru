<?php
require 'db.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) exit("<script>location.href='Login.php'</script>");

$uid = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = '';

// --- 1. PROCESARE FORMULAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_entry'])) {
    $content = trim($_POST['content']);
    $mood = (int) $_POST['mood'];

    // Verificăm intrarea de azi
    $check = $pdo->prepare("SELECT journal_id FROM Journal WHERE user_id=? AND entry_date=?");
    $check->execute([$uid, $today]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE Journal SET content=?, mood_rating=? WHERE journal_id=?")->execute([$content, $mood, $existing['journal_id']]);
        $message = "Jurnal actualizat!";
    } else {
        $pdo->prepare("INSERT INTO Journal (user_id, entry_date, content, mood_rating) VALUES (?, ?, ?, ?)")->execute([$uid, $today, $content, $mood]);
        $message = "Intrare salvată!";

        // --- BADGE LOGIC: JURNALIST ---
        // Verificăm câte intrări are userul
        $count = $pdo->query("SELECT COUNT(*) FROM Journal WHERE user_id=$uid")->fetchColumn();
        if ($count == 1) awardBadge($uid, 'first_journal', $pdo); // Prima intrare
        if ($count == 5) awardBadge($uid, 'writer_5', $pdo);      // 5 intrări
    }
}

// Funcție Helper locală pentru Badges (doar dacă nu ai făcut una globală)
function awardBadge($uid, $code, $pdo) {
    // Insert IGNORE sau verificare
    $stmt = $pdo->prepare("SELECT id FROM User_Badges WHERE user_id=? AND badge_code=?");
    $stmt->execute([$uid, $code]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO User_Badges (user_id, badge_code) VALUES (?, ?)")->execute([$uid, $code]);
    }
}

// --- 2. DATE PENTRU EDITOR ---
$entryToday = $pdo->prepare("SELECT * FROM Journal WHERE user_id=? AND entry_date=?");
$entryToday->execute([$uid, $today]);
$row = $entryToday->fetch();
$contentVal = $row ? $row['content'] : '';
$moodVal = $row ? $row['mood_rating'] : 3;

// --- 3. DATE PENTRU GRAFIC (Ultimele 30 zile) ---
$chartData = $pdo->prepare("SELECT entry_date, mood_rating FROM Journal WHERE user_id=? ORDER BY entry_date ASC LIMIT 30");
$chartData->execute([$uid]);
$dates = [];
$moods = [];
while($c = $chartData->fetch()) {
    $dates[] = date('d M', strtotime($c['entry_date']));
    $moods[] = $c['mood_rating'];
}
?>

<main class="container">
    <div style="margin-bottom:1.5rem;">
        <h2>📝 Jurnalul Zilnic</h2>
        <p class="text-muted">Reflectează asupra zilei tale.</p>
    </div>

    <?php if($message): ?>
        <div style="background:#d1fae5; color:#065f46; padding:1rem; border-radius:8px; margin-bottom:1rem; text-align:center;">
            <i class="fa-solid fa-check"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="flex-cards" style="align-items: flex-start;">
        <section class="card" style="flex:2; min-width:320px;">
            <h3>Azi, <?php echo date('d M Y'); ?></h3>
            <form method="POST">
                <input type="hidden" name="save_entry" value="1">
                <div style="margin-bottom:1.5rem;">
                    <label class="text-muted" style="font-weight:600; display:block; margin-bottom:0.5rem;">Mood:</label>
                    <div style="display:flex; gap:15px; flex-wrap:wrap;">
                        <?php 
                        $mList = [1=>['#ef4444','fa-face-dizzy'], 2=>['#f97316','fa-face-frown'], 3=>['#eab308','fa-face-meh'], 4=>['#3b82f6','fa-face-smile'], 5=>['#10b981','fa-face-laugh-beam']];
                        foreach($mList as $k => $v): $sel = ($moodVal == $k); ?>
                        <label style="cursor:pointer; opacity:<?php echo $sel?'1':'0.4'; ?>;">
                            <input type="radio" name="mood" value="<?php echo $k; ?>" <?php echo $sel?'checked':''; ?> style="display:none;" onchange="this.form.submit()">
                            <i class="fa-solid <?php echo $v[1]; ?>" style="font-size:2rem; color:<?php echo $v[0]; ?>;"></i>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <textarea name="content" style="min-height:200px; resize:vertical;" placeholder="Gândurile tale..."><?php echo htmlspecialchars($contentVal); ?></textarea>
                <button class="btn" style="margin-top:10px;">Salvează</button>
            </form>
        </section>

        <section class="card" style="flex:1; min-width:300px;">
            <h3><i class="fa-solid fa-chart-line" style="color:var(--primary);"></i> Mood History</h3>
            <p class="text-muted" style="font-size:0.9rem;">Evoluția stării tale în ultimele 30 de intrări.</p>
            
            <div style="height:250px; width:100%;">
                <canvas id="moodChart"></canvas>
            </div>
            <?php if(empty($dates)) echo "<small class='text-muted'>Nu există suficiente date.</small>"; ?>
        </section>
    </div>
</main>

<script>
const ctx = document.getElementById('moodChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Stare',
            data: <?php echo json_encode($moods); ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { min: 1, max: 5, ticks: { stepSize: 1 } }
        },
        plugins: { legend: { display: false } }
    }
});
</script>
<?php include 'footer.php'; ?>