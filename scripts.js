// scripts.js - handles Back to Top button visibility and behavior
document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('backToTop');
  if (!btn) return;

  function checkScroll() {
    if (window.scrollY > 300) btn.classList.add('visible');
    else btn.classList.remove('visible');
  }

  window.addEventListener('scroll', checkScroll, {passive:true});
  checkScroll();

  btn.addEventListener('click', function (e) {
    e.preventDefault();
    window.scrollTo({top: 0, behavior: 'smooth'});
  });
});
