<?php include 'header.php'; ?>

<main class="container">
    <section class="card" style="text-align:center; padding:3rem 1.5rem; background: linear-gradient(to bottom right, #ffffff, #f3f4f6);">
        <h1 style="font-size:2.5rem; margin-bottom:1rem; color:var(--primary);">Boost Your Productivity 🚀</h1>
        <p class="text-muted" style="font-size:1.1rem; max-width:600px; margin:0 auto 2rem;">
            ProChecker este instrumentul all-in-one pentru gestionarea sarcinilor și formarea obiceiurilor sănătoase. Simplu, rapid și eficient.
        </p>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="Register.php" class="btn" style="padding:0.8rem 2rem; font-size:1rem;">Începe Gratuit</a>
        <?php else: ?>
            <a href="Dashboard.php" class="btn" style="padding:0.8rem 2rem; font-size:1rem;">Mergi la Dashboard</a>
        <?php endif; ?>
    </section>

    <h2 style="text-align:center; margin:2rem 0;">De ce ProChecker?</h2>

    <div class="flex-cards">
        <div class="card" style="flex:1; min-width:250px; text-align:center;">
            <i class="fa-solid fa-list-check" style="font-size:2.5rem; color:#f59e0b; margin-bottom:1rem;"></i>
            <h3>Task Management</h3>
            <p class="text-muted">Organizează-ți ziua cu liste clare de priorități. Nu rata niciodată un termen limită.</p>
        </div>
        
        <div class="card" style="flex:1; min-width:250px; text-align:center;">
            <i class="fa-solid fa-fire" style="font-size:2.5rem; color:#ef4444; margin-bottom:1rem;"></i>
            <h3>Habit Tracking</h3>
            <p class="text-muted">Construiește obiceiuri durabile cu ajutorul calendarului nostru vizual "Heatmap".</p>
        </div>
        
        <div class="card" style="flex:1; min-width:250px; text-align:center;">
            <i class="fa-solid fa-chart-line" style="font-size:2.5rem; color:#10b981; margin-bottom:1rem;"></i>
            <h3>Statistici Reale</h3>
            <p class="text-muted">Vizualizează progresul tău zilnic prin grafice intuitive și rămâi motivat.</p>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>