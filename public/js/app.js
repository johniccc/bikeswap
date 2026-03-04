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

    // ── Star rating (left-to-right, 1→5) ───────────
    document.querySelectorAll('.star-rating').forEach(function(c) {
        var labels = Array.from(c.querySelectorAll('label'));
        var inputs = Array.from(c.querySelectorAll('input[type="radio"]'));

        function paint(n) {
            labels.forEach(function(l) {
                var v = parseInt(l.getAttribute('data-value'), 10);
                l.textContent = v <= n ? '★' : '☆';
                if (v <= n) {
                    l.classList.add('filled');
                } else {
                    l.classList.remove('filled');
                }
            });
        }

        inputs.forEach(function(inp) {
            inp.addEventListener('change', function() {
                paint(parseInt(this.value, 10));
            });
        });

        labels.forEach(function(l) {
            l.addEventListener('mouseover', function() {
                paint(parseInt(this.getAttribute('data-value'), 10));
            });
        });

        c.addEventListener('mouseleave', function() {
            var checked = c.querySelector('input:checked');
            paint(checked ? parseInt(checked.value, 10) : 0);
        });
    });
})();