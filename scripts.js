document.addEventListener('DOMContentLoaded', function () {
    // --- 1. Back to Top Button Logic ---
    const backToTopBtn = document.getElementById('backToTop');
    
    if (backToTopBtn) {
        // Arată butonul doar când dai scroll în jos
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        // Scroll lin până sus la click
        backToTopBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});

// --- 2. AJAX Toggle Status (Global Function) ---
// Această funcție este apelată din checkbox-urile HTML (onclick="toggleStatus(...)")
function toggleStatus(type, id, el) {
    const originalState = el.checked;
    fetch('ajax_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `type=${type}&id=${id}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            if (d.leveledUp) alert(`🎉 LEVEL UP! Ai ajuns la nivelul ${d.newLevel}!`);
            
            // --- ALERTĂ NOUĂ PENTRU BADGE ---
            if (d.newBadge) alert(`🏆 INSIGNĂ NOUĂ DEBLOCATĂ! Verifică Dashboard-ul.`);
            
            location.reload();
        } else {
            el.checked = !originalState;
        }
    });
}