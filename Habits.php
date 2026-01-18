<?php
require 'db.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) exit("<script>location.href='Login.php'</script>");

$uid = $_SESSION['user_id'];
$m = date('m'); 
$y = date('Y'); 
$daysInMonth = date('t'); 
$monthName = date('F Y');

// --- 1. ADĂUGARE / ȘTERGERE OBICEI ---
if (isset($_POST['add_h'])) { 
    $pdo->prepare("INSERT INTO Habits (user_id, title) VALUES (?, ?)")->execute([$uid, trim($_POST['title'])]); 
    echo "<script>location.href='Habits.php'</script>";
}
if (isset($_POST['del_h'])) { 
    $pdo->prepare("DELETE FROM Habits WHERE habit_id=? AND user_id=?")->execute([$_POST['hid'], $uid]); 
    echo "<script>location.href='Habits.php'</script>";
}

// --- 2. TOGGLE LOGIC + UPDATE XP & LEVEL ---
if (isset($_GET['tg'])) {
    $dt = sprintf("%s-%s-%02d", $y, $m, $_GET['d']);
    $hid = $_GET['tg'];
    
    // Verificăm starea actuală
    $exists = $pdo->prepare("SELECT log_id FROM Habit_Logs WHERE habit_id=? AND completed_date=?");
    $exists->execute([$hid, $dt]);
    $logEntry = $exists->fetch();

    $xpChange = 0; // Variabilă pentru a urmări XP-ul

    if ($logEntry) {
        // --- DEBIFARE (Ștergem log-ul) ---
        $pdo->prepare("DELETE FROM Habit_Logs WHERE log_id=?")->execute([$logEntry['log_id']]);
        $xpChange = -5; // Scădem XP
    } else {
        // --- BIFARE (Adăugăm log-ul) ---
        $own = $pdo->prepare("SELECT habit_id FROM Habits WHERE habit_id=? AND user_id=?");
        $own->execute([$hid, $uid]);
        if ($own->fetch()) {
            $pdo->prepare("INSERT INTO Habit_Logs (habit_id, completed_date) VALUES (?, ?)")->execute([$hid, $dt]);
            $xpChange = 5; // Adăugăm XP
        }
    }

    // --- LOGICA DE LEVEL UP (Adăugată) ---
    if ($xpChange != 0) {
        $uStmt = $pdo->prepare("SELECT xp, level FROM Users WHERE user_id=?");
        $uStmt->execute([$uid]);
        $userData = $uStmt->fetch();

        if ($userData) {
            $newXP = max(0, $userData['xp'] + $xpChange);
            $newLevel = floor($newXP / 100) + 1;
            
            $pdo->prepare("UPDATE Users SET xp=?, level=? WHERE user_id=?")->execute([$newXP, $newLevel, $uid]);
        }
    }

    echo "<script>location.href='Habits.php'</script>";
}

// --- 3. DATE PENTRU TABEL ---
$habits = $pdo->prepare("SELECT * FROM Habits WHERE user_id=?"); 
$habits->execute([$uid]);
$hList = $habits->fetchAll();
$totalHabits = count($hList);

$logs = $pdo->prepare("SELECT habit_id, DAY(completed_date) as d FROM Habit_Logs WHERE MONTH(completed_date)=? AND YEAR(completed_date)=? AND habit_id IN (SELECT habit_id FROM Habits WHERE user_id=?)");
$logs->execute([$m, $y, $uid]);
$map = []; foreach($logs->fetchAll() as $l) $map[$l['habit_id']][$l['d']] = true;

// --- 4. DATE PENTRU GRAFICE ---

// A. Săptămâna Asta (Calcul PHP precis Luni-Duminică)
$startWeek = date('Y-m-d', strtotime('monday this week'));
$endWeek   = date('Y-m-d', strtotime('sunday this week'));

$weekStats = $pdo->prepare("
    SELECT COUNT(*) FROM Habit_Logs 
    WHERE habit_id IN (SELECT habit_id FROM Habits WHERE user_id=?) 
    AND completed_date BETWEEN ? AND ?
");
$weekStats->execute([$uid, $startWeek, $endWeek]);
$weekDone = $weekStats->fetchColumn();

$weekTotal = $totalHabits * 7; 
$weekRemaining = max(0, $weekTotal - $weekDone);

// B. Luna Asta
$monthStats = $pdo->prepare("
    SELECT COUNT(*) FROM Habit_Logs 
    WHERE habit_id IN (SELECT habit_id FROM Habits WHERE user_id=?) 
    AND MONTH(completed_date) = ? AND YEAR(completed_date) = ?
");
$monthStats->execute([$uid, $m, $y]);
$monthDone = $monthStats->fetchColumn();
$monthTotal = $totalHabits * $daysInMonth; 
$monthRemaining = max(0, $monthTotal - $monthDone);
?>

<main class="container">
    
    <div class="card">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <div>
                <h2><i class="fa-solid fa-fire text-muted"></i> Habit Tracker</h2>
                <p class="text-muted">Monitorizează consistența ta pentru <strong><?php echo $monthName; ?></strong>.</p>
            </div>
            
            <form method="POST" style="display:flex; gap:10px; margin:0;">
                <input name="title" placeholder="Obicei nou..." required style="width:200px; margin:0;">
                <button name="add_h" class="btn"><i class="fa-solid fa-plus"></i></button>
            </form>
        </div>

        <div class="habit-controls" style="display:flex; align-items:center; justify-content:space-between; background:#f9fafb; padding:10px; border:1px solid var(--border); border-bottom:none; border-radius:8px 8px 0 0;">
            <button id="prevDays" class="btn btn-sm" onclick="changeDays(-7)"><i class="fa-solid fa-chevron-left"></i> Anterior</button>
            <span id="rangeDisplay" style="font-weight:600; color:var(--text-main);">Zilele ...</span>
            <button id="nextDays" class="btn btn-sm" onclick="changeDays(7)">Următor <i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <div class="habit-table-wrapper" style="border-top:none; border-radius:0 0 8px 8px;">
            <table class="habit-table" id="habitsTable">
                <thead>
                    <tr>
                        <th style="text-align:left; min-width:150px; padding-left:10px;">Obicei</th>
                        <?php for($i=1;$i<=$daysInMonth;$i++): ?>
                            <th class="day-col <?php echo ($i==date('d'))?'active-day':''; ?>" data-day="<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </th>
                        <?php endfor; ?>
                        <th>Act</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($hList as $h): ?>
                    <tr>
                        <td style="font-weight:600; padding:10px; color:var(--text-main);">
                            <?php echo htmlspecialchars($h['title']); ?>
                        </td>
                        <?php for($d=1;$d<=$daysInMonth;$d++): 
                            $isActive = isset($map[$h['habit_id']][$d]); ?>
                            <td class="day-col" data-day="<?php echo $d; ?>" style="padding:2px;">
                                <a href="?tg=<?php echo $h['habit_id']; ?>&d=<?php echo $d; ?>" 
                                   class="habit-cell <?php echo $isActive?'done':''; ?>"
                                   title="Ziua <?php echo $d; ?>">
                                </a>
                            </td>
                        <?php endfor; ?>
                        <td style="text-align:center;">
                            <form method="POST" onsubmit="return confirm('Sigur ștergi?');" style="margin:0;">
                                <input type="hidden" name="hid" value="<?php echo $h['habit_id']; ?>">
                                <button name="del_h" class="btn-icon" style="color:var(--text-muted);"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($hList)) echo "<p class='text-muted text-center' style='margin-top:2rem; padding:1rem;'>Nu ai adăugat încă obiceiuri.</p>"; ?>
        </div>
    </div>

    <div class="flex-cards" style="margin-top: 1.5rem;">
        
        <section class="card" style="flex:1; min-width:300px; text-align:center;">
            <h3><i class="fa-solid fa-calendar-week" style="color:var(--primary);"></i> Săptămâna Asta</h3>
            <?php if($totalHabits > 0): ?>
                <div class="chart-container" style="height:180px; width:180px; margin: 0 auto;">
                    <canvas id="chartWeek"></canvas>
                </div>
                <p style="margin-top:10px; font-weight:600;">
                    <?php echo $weekDone; ?> / <?php echo $weekTotal; ?> bifări
                </p>
                <small class="text-muted">Procentaj completare</small>
            <?php else: ?>
                <p class="text-muted" style="padding:2rem;">Adaugă obiceiuri pentru statistici.</p>
            <?php endif; ?>
        </section>

        <section class="card" style="flex:1; min-width:300px; text-align:center;">
            <h3><i class="fa-regular fa-calendar-days" style="color:var(--success);"></i> Luna Asta</h3>
            <?php if($totalHabits > 0): ?>
                <div class="chart-container" style="height:180px; width:180px; margin: 0 auto;">
                    <canvas id="chartMonth"></canvas>
                </div>
                <p style="margin-top:10px; font-weight:600;">
                    <?php echo $monthDone; ?> / <?php echo $monthTotal; ?> bifări
                </p>
                <small class="text-muted">Procentaj completare</small>
            <?php else: ?>
                <p class="text-muted" style="padding:2rem;">Adaugă obiceiuri pentru statistici.</p>
            <?php endif; ?>
        </section>

    </div>

</main>

<script>
    // --- 1. SLIDER LOGIC ---
    let currentStartDay = <?php echo max(1, date('d') - 3); ?>;
    const totalDays = <?php echo $daysInMonth; ?>;
    const daysToShow = 7; 

    function updateVisibility() {
        if (currentStartDay < 1) currentStartDay = 1;
        if (currentStartDay > totalDays - daysToShow + 1) currentStartDay = Math.max(1, totalDays - daysToShow + 1);
        const endDay = currentStartDay + daysToShow - 1;
        document.getElementById('rangeDisplay').innerText = `Zilele ${currentStartDay} - ${Math.min(endDay, totalDays)}`;
        
        document.querySelectorAll('.day-col').forEach(col => {
            const day = parseInt(col.getAttribute('data-day'));
            col.style.display = (day >= currentStartDay && day <= endDay) ? 'table-cell' : 'none';
        });
        
        document.getElementById('prevDays').disabled = (currentStartDay <= 1);
        document.getElementById('nextDays').disabled = (endDay >= totalDays);
        document.getElementById('prevDays').style.opacity = (currentStartDay <= 1) ? 0.5 : 1;
        document.getElementById('nextDays').style.opacity = (endDay >= totalDays) ? 0.5 : 1;
    }
    function changeDays(offset) { currentStartDay += offset; updateVisibility(); }

    // --- 2. CHARTS LOGIC ---
    document.addEventListener('DOMContentLoaded', () => {
        // Init Slider
        if(currentStartDay + daysToShow > totalDays) currentStartDay = totalDays - daysToShow + 1;
        updateVisibility();

        // Init Charts (Doar dacă există obiceiuri)
        <?php if($totalHabits > 0): ?>
            const commonOpts = { cutout: '65%', responsive: true, plugins: { legend: { position:'bottom' } } };
            
            // Săptămână
            new Chart(document.getElementById('chartWeek'), {
                type: 'doughnut',
                data: {
                    labels: ['Completat', 'Rămas'],
                    datasets: [{
                        data: [<?php echo $weekDone; ?>, <?php echo $weekRemaining; ?>],
                        backgroundColor: ['#4f46e5', '#e5e7eb'], 
                        borderWidth: 0
                    }]
                },
                options: commonOpts
            });

            // Lună
            new Chart(document.getElementById('chartMonth'), {
                type: 'doughnut',
                data: {
                    labels: ['Completat', 'Rămas'],
                    datasets: [{
                        data: [<?php echo $monthDone; ?>, <?php echo $monthRemaining; ?>],
                        backgroundColor: ['#10b981', '#e5e7eb'],
                        borderWidth: 0
                    }]
                },
                options: commonOpts
            });
        <?php endif; ?>
    });
</script>

<?php include 'footer.php'; ?>