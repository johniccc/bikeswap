/**
 * BikeSwap – Main application JavaScript
 */

(function() {
    'use strict';

    // ── Mobile nav toggle ──────────────────────────
    var toggle = document.getElementById('nav-toggle');
    var links = document.getElementById('nav-links');

    if (toggle && links) {
        toggle.addEventListener('click', function() {
            links.classList.toggle('active');
            toggle.textContent = links.classList.contains('active') ? '✕' : '☰';
        });
    }

    // ── Auto-dismiss alerts after 5 seconds ────────
    var alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 300);
        }, 5000);
    });
})();