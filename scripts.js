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
    // Salvăm starea inițială în caz că serverul dă eroare
    const originalState = el.checked;

    fetch('ajax_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `type=${type}&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reîncărcăm pagina pentru a actualiza graficele și culorile
            // (Fiind o aplicație PHP clasică, reload-ul asigură consistența datelor)
            location.reload(); 
        } else {
            // Revenim la starea inițială dacă serverul nu a validat schimbarea
            el.checked = !originalState;
            alert('Nu s-a putut actualiza statusul. Încearcă din nou.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        el.checked = !originalState;
        alert('Eroare de conexiune.');
    });
}