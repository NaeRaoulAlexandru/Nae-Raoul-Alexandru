<?php
require 'db.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) exit("<script>location.href='Login.php'</script>");

$uid = $_SESSION['user_id'];
$m = date('m'); 
$y = date('Y'); 
$days = date('t'); 
$monthName = date('F Y');

// Logică adăugare/ștergere/toggle
if (isset($_POST['add_h'])) { $pdo->prepare("INSERT INTO Habits (user_id, title) VALUES (?, ?)")->execute([$uid, trim($_POST['title'])]); }
if (isset($_POST['del_h'])) { $pdo->prepare("DELETE FROM Habits WHERE habit_id=? AND user_id=?")->execute([$_POST['hid'], $uid]); }
if (isset($_GET['tg'])) {
    $dt = sprintf("%s-%s-%02d", $y, $m, $_GET['d']);
    $hid = $_GET['tg'];
    $exists = $pdo->prepare("SELECT log_id FROM Habit_Logs WHERE habit_id=? AND completed_date=?");
    $exists->execute([$hid, $dt]);
    if ($l = $exists->fetch()) $pdo->prepare("DELETE FROM Habit_Logs WHERE log_id=?")->execute([$l['log_id']]);
    else $pdo->prepare("INSERT INTO Habit_Logs (habit_id, completed_date) VALUES (?, ?)")->execute([$hid, $dt]);
    echo "<script>location.href='Habits.php'</script>";
}

$habits = $pdo->prepare("SELECT * FROM Habits WHERE user_id=?"); $habits->execute([$uid]);
$hList = $habits->fetchAll();
$logs = $pdo->prepare("SELECT habit_id, DAY(completed_date) as d FROM Habit_Logs WHERE MONTH(completed_date)=? AND YEAR(completed_date)=? AND habit_id IN (SELECT habit_id FROM Habits WHERE user_id=?)");
$logs->execute([$m, $y, $uid]);
$map = []; foreach($logs->fetchAll() as $l) $map[$l['habit_id']][$l['d']] = true;
?>

<main class="container">
    <div class="card">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <div>
                <h2><i class="fa-solid fa-fire text-muted"></i> Habit Tracker</h2>
                <p class="text-muted">Monitorizează consistența ta pentru <strong><?php echo $monthName; ?></strong>.</p>
            </div>
            
            <form method="POST" style="display:flex; gap:10px; margin:0;">
                <input name="title" placeholder="Obicei nou (ex: Apă 2L)" required style="width:200px; margin:0;">
                <button name="add_h" class="btn"><i class="fa-solid fa-plus"></i></button>
            </form>
        </div>

        <div class="habit-controls" style="display:flex; align-items:center; justify-content:space-between; background:#f9fafb; padding:10px; border:1px solid var(--border); border-bottom:none; border-radius:8px 8px 0 0;">
            <button id="prevDays" class="btn btn-sm" onclick="changeDays(-7)"><i class="fa-solid fa-chevron-left"></i> Săptămâna anterioară</button>
            <span id="rangeDisplay" style="font-weight:600; color:var(--text-main);">Zilele ...</span>
            <button id="nextDays" class="btn btn-sm" onclick="changeDays(7)">Săptămâna viitoare <i class="fa-solid fa-chevron-right"></i></button>
        </div>

        <div class="habit-table-wrapper" style="border-top:none; border-radius:0 0 8px 8px;">
            <table class="habit-table" id="habitsTable">
                <thead>
                    <tr>
                        <th style="text-align:left; min-width:150px; padding-left:10px;">Obicei</th>
                        <?php for($i=1;$i<=$days;$i++): ?>
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
                        
                        <?php for($d=1;$d<=$days;$d++): 
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
</main>

<script>
    let currentStartDay = <?php echo max(1, date('d') - 3); ?>; // Pornim centrat pe ziua curentă (sau aproape)
    const totalDays = <?php echo $days; ?>;
    const daysToShow = 7; // Câte zile arătăm o dată (Săptămânal)

    function updateVisibility() {
        // Corecții limite
        if (currentStartDay < 1) currentStartDay = 1;
        if (currentStartDay > totalDays - daysToShow + 1) currentStartDay = Math.max(1, totalDays - daysToShow + 1);

        const endDay = currentStartDay + daysToShow - 1;
        
        // Actualizăm textul
        document.getElementById('rangeDisplay').innerText = `Zilele ${currentStartDay} - ${Math.min(endDay, totalDays)}`;

        // Ascundem/Afișăm coloane
        const cols = document.querySelectorAll('.day-col');
        cols.forEach(col => {
            const day = parseInt(col.getAttribute('data-day'));
            if (day >= currentStartDay && day <= endDay) {
                col.style.display = 'table-cell';
            } else {
                col.style.display = 'none';
            }
        });

        // Dezactivăm butoanele la capete
        document.getElementById('prevDays').disabled = (currentStartDay <= 1);
        document.getElementById('nextDays').disabled = (endDay >= totalDays);
        
        // Stil butoane disabled
        document.getElementById('prevDays').style.opacity = (currentStartDay <= 1) ? 0.5 : 1;
        document.getElementById('nextDays').style.opacity = (endDay >= totalDays) ? 0.5 : 1;
    }

    function changeDays(offset) {
        currentStartDay += offset;
        updateVisibility();
    }

    // Inițializare la încărcare
    document.addEventListener('DOMContentLoaded', () => {
        // Dacă e sfârșit de lună, ajustăm startul
        if(currentStartDay + daysToShow > totalDays) {
            currentStartDay = totalDays - daysToShow + 1;
        }
        updateVisibility();
    });
</script>

<?php include 'footer.php'; ?>