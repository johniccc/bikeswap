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

// ── QR Scanner ────────────────────────────────────
(function() {
    'use strict';

    var modal = document.getElementById('qr-modal');
    var video = document.getElementById('qr-video');
    var canvas = document.getElementById('qr-canvas');
    var statusEl = document.getElementById('qr-status');
    var fileInput = document.getElementById('qr-file-input');

    if (!modal) return; // not on a page with QR modal

    var stream = null;
    var animFrame = null;
    var ctx = canvas.getContext('2d');

    function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg;
    }

    function handleCode(data) {
        try {
            var url = new URL(data);
            if (url.hostname === window.location.hostname && url.pathname.startsWith('/bike/')) {
                window.location.href = data;
            } else {
                setStatus('Neplatný QR kód BikeSwap.');
            }
        } catch(e) {
            setStatus('Neplatný QR kód.');
        }
    }

    function stopCamera() {
        if (animFrame) { cancelAnimationFrame(animFrame); animFrame = null; }
        if (stream) { stream.getTracks().forEach(function(t) { t.stop(); }); stream = null; }
    }

    function scanFrame() {
        if (!video || video.readyState < video.HAVE_ENOUGH_DATA) {
            animFrame = requestAnimationFrame(scanFrame);
            return;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        if (window.jsQR) {
            var code = window.jsQR(imageData.data, imageData.width, imageData.height);
            if (code) {
                stopCamera();
                handleCode(code.data);
                return;
            }
        }
        animFrame = requestAnimationFrame(scanFrame);
    }

    function openModal() {
        modal.classList.add('active');
        setStatus('Spouštím kameru…');
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Kamera není dostupná. Nahrajte obrázek níže.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(s) {
                stream = s;
                video.srcObject = s;
                video.play();
                setStatus('Nasměrujte kameru na QR kód.');
                animFrame = requestAnimationFrame(scanFrame);
            })
            .catch(function(err) {
                var msg = err.name === 'NotAllowedError'
                    ? 'Přístup ke kameře byl odepřen. Nahrajte obrázek níže.'
                    : 'Kamera není dostupná. Nahrajte obrázek níže.';
                setStatus(msg);
            });
    }

    function closeModal() {
        stopCamera();
        modal.classList.remove('active');
        setStatus('Spouštím kameru…');
        video.srcObject = null;
    }

    // Open buttons
    ['open-qr-scanner', 'open-qr-scanner-mobile'].forEach(function(id) {
        var btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', openModal);
    });

    // Close button and overlay click
    var closeBtn = document.getElementById('close-qr-modal');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    // File upload fallback
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            setStatus('Načítám obrázek…');
            var reader = new FileReader();
            reader.onload = function(ev) {
                var img = new Image();
                img.onload = function() {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    if (window.jsQR) {
                        var code = window.jsQR(imageData.data, imageData.width, imageData.height);
                        if (code) {
                            handleCode(code.data);
                        } else {
                            setStatus('QR kód v obrázku nenalezen.');
                        }
                    }
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
            fileInput.value = '';
        });
    }
})();

// ── Chat message polling (3s long polling) ──────────────────────
(function() {
    'use strict';

    var container = document.getElementById('messages');
    if (!container) return;

    var pollUrl = container.getAttribute('data-poll-url');
    var lastId  = parseInt(container.getAttribute('data-last-id') || '0', 10);

    if (!pollUrl) return;

    function appendMessage(msg) {
        var div = document.createElement('div');
        div.className = 'message' +
            (msg.mine   ? ' mine'           : '') +
            (msg.system ? ' message-system' : '');

        var bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        bubble.textContent = msg.text; // NEVER innerHTML — textContent is XSS-safe

        div.appendChild(bubble);

        if (!msg.system) {
            var meta = document.createElement('div');
            meta.className = 'message-meta';
            meta.textContent = (msg.label || '') + ' · ' + (msg.time || '');
            div.appendChild(meta);
        }

        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function poll() {
        fetch(pollUrl + '?after=' + lastId, { credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (data && data.messages && data.messages.length > 0) {
                    data.messages.forEach(function(msg) {
                        appendMessage(msg);
                        if (msg.id > lastId) { lastId = msg.id; }
                    });
                }
            })
            .catch(function() {})
            .finally(function() {
                setTimeout(poll, 3000);
            });
    }

    setTimeout(poll, 3000);
})();