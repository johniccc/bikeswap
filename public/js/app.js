/**
 * BikeSwap – Main application JavaScript
 * Handles: Lucide icons, burger menu, auth modal, QR scanner, flash dismiss,
 * chat polling, star rating, photo upload preview, notification polling,
 * scroll animations, toasts, geolocation helper, serial number lookup.
 */

(function() {
    'use strict';

    // ── 1. Lucide icons initialization ──────────────
    function initIcons() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
    initIcons();

    // ── 2. Burger menu toggle (public layout mobile) ──
    var burgerToggle = document.getElementById('burger-toggle');
    var mobileMenu = document.getElementById('mobile-menu');
    function closeMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('open');
        // Re-query since initIcons replaces the element
        var icon = document.getElementById('burger-icon');
        if (icon) {
            icon.setAttribute('data-lucide', 'menu');
            initIcons();
        }
    }

    if (burgerToggle && mobileMenu) {
        burgerToggle.addEventListener('click', function() {
            var isOpen = mobileMenu.classList.toggle('open');
            // Re-query since initIcons replaces the element
            var icon = document.getElementById('burger-icon');
            if (icon) {
                icon.setAttribute('data-lucide', isOpen ? 'x' : 'menu');
                initIcons();
            }
        });

        // Close menu when clicking any link or button
        mobileMenu.querySelectorAll('a, button').forEach(function(el) {
            el.addEventListener('click', closeMobileMenu);
        });
    }

    // ── 3. Auth modal ───────────────────────────────
    var authModal = document.getElementById('auth-modal');

    function openAuthModal(tab) {
        if (!authModal) return;
        authModal.classList.add('active');
        document.body.classList.add('modal-blur');
        if (tab) switchAuthTab(tab);
    }

    function closeAuthModal() {
        if (!authModal) return;
        authModal.classList.remove('active');
        document.body.classList.remove('modal-blur');
    }

    function switchAuthTab(tab) {
        if (!authModal) return;
        var loginForm = document.getElementById('auth-form-login');
        var registerForm = document.getElementById('auth-form-register');
        var tabs = authModal.querySelectorAll('.auth-tab');
        var title = document.getElementById('auth-modal-title');

        // Toggle forms
        if (tab === 'login') {
            if (loginForm) loginForm.classList.add('active');
            if (registerForm) registerForm.classList.remove('active');
            if (title) title.textContent = 'Přihlášení';
        } else {
            if (loginForm) loginForm.classList.remove('active');
            if (registerForm) registerForm.classList.add('active');
            if (title) title.textContent = 'Registrace';
        }

        // Toggle tab buttons
        tabs.forEach(function(t) {
            if (t.getAttribute('data-tab') === tab) {
                t.classList.add('active');
            } else {
                t.classList.remove('active');
            }
        });
    }

    // Open auth modal buttons
    document.querySelectorAll('.open-auth-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openAuthModal(this.getAttribute('data-tab') || 'login');
        });
    });

    // Auth tab switching (tabs + inline "switch" links)
    document.querySelectorAll('.auth-tab, .auth-switch-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            switchAuthTab(this.getAttribute('data-tab'));
        });
    });

    // Close auth modal
    var closeAuthBtn = document.getElementById('close-auth-modal');
    if (closeAuthBtn) closeAuthBtn.addEventListener('click', closeAuthModal);
    if (authModal) {
        authModal.addEventListener('click', function(e) {
            if (e.target === authModal) closeAuthModal();
        });
    }

    // Expose for auto-open from login/register pages
    window.BikeSwap = window.BikeSwap || {};
    window.BikeSwap.openAuthModal = openAuthModal;

    // ── 4. (Flash messages converted to toasts in section 15) ──

    // ── 5. QR Scanner ───────────────────────────────
    (function() {
        var modal = document.getElementById('qr-modal');
        var video = document.getElementById('qr-video');
        var canvas = document.getElementById('qr-canvas');
        var statusEl = document.getElementById('qr-status');
        var fileInput = document.getElementById('qr-file-input');

        if (!modal) return;

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
                    setStatus('Neplatny QR kod BikeSwap.');
                }
            } catch(e) {
                setStatus('Neplatny QR kod.');
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

        function openQRModal() {
            modal.classList.add('active');
            document.body.classList.add('modal-blur');
            setStatus('Spoustim kameru...');
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setStatus('Kamera neni dostupna. Nahrajte obrazek nize.');
                return;
            }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(function(s) {
                    stream = s;
                    video.srcObject = s;
                    video.play();
                    setStatus('Nasmerujte kameru na QR kod.');
                    animFrame = requestAnimationFrame(scanFrame);
                })
                .catch(function(err) {
                    var msg = err.name === 'NotAllowedError'
                        ? 'Pristup ke kamere byl odepren. Nahrajte obrazek nize.'
                        : 'Kamera neni dostupna. Nahrajte obrazek nize.';
                    setStatus(msg);
                });
        }

        function closeQRModal() {
            stopCamera();
            modal.classList.remove('active');
            document.body.classList.remove('modal-blur');
            setStatus('');
            if (video) video.srcObject = null;
        }

        // Open QR scanner buttons
        ['open-qr-scanner', 'open-qr-scanner-mobile'].forEach(function(id) {
            var btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', openQRModal);
        });

        // Close button + overlay click
        var closeBtn = document.getElementById('close-qr-modal');
        if (closeBtn) closeBtn.addEventListener('click', closeQRModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeQRModal();
        });

        // File upload fallback
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;
                setStatus('Nacitam obrazek...');
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
                                setStatus('QR kod v obrazku nenalezen.');
                            }
                        }
                    };
                    img.src = ev.target.result;
                };
                reader.readAsDataURL(file);
                fileInput.value = '';
            });
        }

        // Serial number lookup
        var serialForm = document.getElementById('serial-lookup-form');
        if (serialForm) {
            serialForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var input = document.getElementById('serial-number-input');
                if (input && input.value.trim()) {
                    window.location.href = '/bike/serial/' + encodeURIComponent(input.value.trim());
                }
            });
        }
    })();

    // ── 6. Chat message polling (3s) + status changes ─
    (function() {
        var container = document.getElementById('messages');
        if (!container) return;

        var pollUrl = container.getAttribute('data-poll-url');
        var lastId = parseInt(container.getAttribute('data-last-id') || '0', 10);
        if (!pollUrl) return;

        // Track current status and review count to detect changes
        var currentStatus = container.getAttribute('data-status') || null;
        var currentReviewCount = -1;

        function appendMessage(msg) {
            // Remove "no messages" placeholder
            var placeholder = container.querySelector('.text-muted');
            if (placeholder && !container.querySelector('.message')) {
                placeholder.remove();
            }

            var div = document.createElement('div');
            div.className = 'message' +
                (msg.mine ? ' mine' : '') +
                (msg.system ? ' message-system' : '');

            var bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = msg.text;
            div.appendChild(bubble);

            if (!msg.system) {
                var meta = document.createElement('div');
                meta.className = 'message-meta';
                meta.textContent = (msg.label || '') + ' \u00B7 ' + (msg.time || '');
                div.appendChild(meta);
            }

            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        function poll() {
            fetch(pollUrl + '?after=' + lastId, { credentials: 'same-origin' })
                .then(function(r) { return r.ok ? r.json() : null; })
                .then(function(data) {
                    if (!data) return;

                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(function(msg) {
                            appendMessage(msg);
                            if (msg.id > lastId) lastId = msg.id;
                        });
                    }

                    // Detect status change and reload page for fresh actions
                    var needsReload = false;
                    if (data.status_label && currentStatus && data.status_label !== currentStatus) {
                        if (window.BikeSwap && window.BikeSwap.toast) {
                            window.BikeSwap.toast('Stav rezervace se změnil: ' + data.status_label, 'info');
                        }
                        needsReload = true;
                    }

                    // Detect new reviews
                    if (typeof data.review_count === 'number') {
                        if (currentReviewCount >= 0 && data.review_count !== currentReviewCount) {
                            if (window.BikeSwap && window.BikeSwap.toast) {
                                window.BikeSwap.toast('Hodnocení bylo aktualizováno', 'info');
                            }
                            needsReload = true;
                        }
                        currentReviewCount = data.review_count;
                    }

                    if (needsReload) {
                        setTimeout(function() { window.location.reload(); }, 1500);
                        return;
                    }
                })
                .catch(function() {})
                .finally(function() {
                    setTimeout(poll, 3000);
                });
        }

        setTimeout(poll, 3000);
    })();

    // ── 7. Star rating (Lucide star icons) ──────────
    document.querySelectorAll('.star-rating').forEach(function(c) {
        var labels = Array.from(c.querySelectorAll('label'));
        var inputs = Array.from(c.querySelectorAll('input[type="radio"]'));

        function paint(n) {
            labels.forEach(function(l) {
                var v = parseInt(l.getAttribute('data-value'), 10);
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

    // ── 8. Photo upload preview ─────────────────────
    (function() {
        var input = document.getElementById('photo-input');
        var preview = document.getElementById('photo-preview');
        var hiddenPrimary = document.getElementById('primary-index');

        if (!input || !preview) return;

        var files = [];
        var primaryIdx = 0;

        function render() {
            while (preview.firstChild) preview.removeChild(preview.firstChild);
            files.forEach(function(file, i) {
                var card = document.createElement('div');
                card.className = 'photo-preview-card' + (i === primaryIdx ? ' photo-preview-primary' : '');

                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = '';
                img.className = 'photo-preview-img';
                card.appendChild(img);

                var actions = document.createElement('div');
                actions.className = 'photo-preview-actions';

                if (i !== primaryIdx) {
                    var setPrimary = document.createElement('button');
                    setPrimary.type = 'button';
                    setPrimary.className = 'photo-action-btn';
                    setPrimary.title = 'Nastavit jako hlavní';
                    setPrimary.innerHTML = '<i data-lucide="star"></i>';
                    (function(idx) {
                        setPrimary.addEventListener('click', function() {
                            primaryIdx = idx;
                            hiddenPrimary.value = String(idx);
                            render();
                        });
                    })(i);
                    actions.appendChild(setPrimary);
                } else {
                    var badge = document.createElement('span');
                    badge.className = 'photo-preview-badge';
                    badge.innerHTML = '<i data-lucide="star"></i>';
                    actions.appendChild(badge);
                }

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'photo-action-btn photo-action-danger';
                remove.title = 'Odebrat';
                remove.innerHTML = '<i data-lucide="x"></i>';
                (function(idx) {
                    remove.addEventListener('click', function() {
                        URL.revokeObjectURL(img.src);
                        files.splice(idx, 1);
                        if (primaryIdx >= files.length) primaryIdx = Math.max(0, files.length - 1);
                        hiddenPrimary.value = String(primaryIdx);
                        syncFileInput();
                        render();
                    });
                })(i);
                actions.appendChild(remove);

                card.appendChild(actions);
                preview.appendChild(card);
            });
            initIcons();
        }

        function syncFileInput() {
            var dt = new DataTransfer();
            files.forEach(function(f) { dt.items.add(f); });
            input.files = dt.files;
        }

        input.addEventListener('change', function() {
            Array.from(input.files).forEach(function(f) { files.push(f); });
            syncFileInput();
            render();
        });
    })();

    // ── 9. Notification polling (5s, real-time) ─────
    (function() {
        var desktopBadge = document.getElementById('notif-count-desktop');
        var mobileBadge = document.getElementById('notif-count-mobile');
        var publicBadge = document.getElementById('notif-count-public');

        if (!desktopBadge && !mobileBadge && !publicBadge) return;

        function updateBadges(count) {
            [desktopBadge, mobileBadge, publicBadge].forEach(function(badge) {
                if (!badge) return;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            });
        }

        var lastKnownCount = -1;

        function pollNotifications() {
            fetch('/notifications/count', { credentials: 'same-origin' })
                .then(function(r) { return r.ok ? r.json() : null; })
                .then(function(data) {
                    if (data && typeof data.unread_count === 'number') {
                        var count = data.unread_count;
                        // Show toast when count increases (not on first poll)
                        if (lastKnownCount >= 0 && count > lastKnownCount && window.BikeSwap && window.BikeSwap.toast) {
                            var diff = count - lastKnownCount;
                            window.BikeSwap.toast(
                                diff === 1 ? 'Máte nové oznámení' : 'Máte ' + diff + ' nová oznámení',
                                'info'
                            );
                        }
                        lastKnownCount = count;
                        updateBadges(count);
                    }
                })
                .catch(function() {})
                .finally(function() {
                    setTimeout(pollNotifications, 5000);
                });
        }

        // Initial poll after 1s
        setTimeout(pollNotifications, 1000);
    })();

    // ── 10. Scroll animations (IntersectionObserver) ─
    (function() {
        var animItems = document.querySelectorAll('.animate-in');
        if (!animItems.length) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animItems.forEach(function(item) {
            observer.observe(item);
        });
    })();

    // ── 11. Toast utility ───────────────────────────
    window.BikeSwap = window.BikeSwap || {};
    window.BikeSwap.toast = function(message, type) {
        var container = document.getElementById('toast-container');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type || 'info');
        toast.textContent = message;
        container.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(function() {
            toast.classList.add('toast-visible');
        });

        setTimeout(function() {
            toast.classList.remove('toast-visible');
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    };

    // ── 12. Geolocation helper (data-attribute driven) ─
    document.querySelectorAll('[data-geolocate]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var latInput = document.getElementById(btn.getAttribute('data-lat-input'));
            var lngInput = document.getElementById(btn.getAttribute('data-lng-input'));
            var status = btn.parentElement.querySelector('.geo-status');

            if (!navigator.geolocation) {
                if (status) status.textContent = 'Geolokace neni podporovana vasim prohlizecem.';
                return;
            }

            if (window.isSecureContext === false) {
                if (status) status.textContent = 'Geolokace vyzaduje HTTPS pripojeni.';
                return;
            }

            if (status) status.textContent = 'Zjistuji polohu...';
            btn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    if (latInput) latInput.value = position.coords.latitude;
                    if (lngInput) lngInput.value = position.coords.longitude;
                    if (status) {
                        status.textContent = 'Poloha zjistena (' +
                            position.coords.latitude.toFixed(5) + ', ' +
                            position.coords.longitude.toFixed(5) + ')';
                    }
                    btn.disabled = false;
                },
                function(error) {
                    var msgs = {
                        1: 'Pristup k poloze byl zamitnut.',
                        2: 'Poloha neni dostupna.',
                        3: 'Zjistovani polohy vyprelo.'
                    };
                    if (status) status.textContent = msgs[error.code] || 'Chyba geolokace.';
                    btn.disabled = false;
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
            );
        });
    });

    // ── 13. Lightbox ──────────────────────────────────
    (function() {
        var items = document.querySelectorAll('[data-lightbox]');
        if (!items.length) return;

        var urls = [];
        items.forEach(function(el) { urls.push(el.getAttribute('data-lightbox')); });
        var currentIdx = 0;

        function open(idx) {
            currentIdx = idx;
            var overlay = document.createElement('div');
            overlay.className = 'lightbox-overlay';
            overlay.id = 'lightbox';

            var img = document.createElement('img');
            img.src = urls[idx];
            overlay.appendChild(img);

            if (urls.length > 1) {
                var prev = document.createElement('button');
                prev.className = 'lightbox-nav lightbox-prev';
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
                prev.addEventListener('click', function(e) { e.stopPropagation(); navigate(-1); });
                overlay.appendChild(prev);

                var next = document.createElement('button');
                next.className = 'lightbox-nav lightbox-next';
                next.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
                next.addEventListener('click', function(e) { e.stopPropagation(); navigate(1); });
                overlay.appendChild(next);

                var counter = document.createElement('div');
                counter.className = 'lightbox-counter';
                counter.textContent = (idx + 1) + ' / ' + urls.length;
                overlay.appendChild(counter);
            }

            var closeBtn = document.createElement('button');
            closeBtn.className = 'lightbox-close';
            closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            closeBtn.addEventListener('click', function(e) { e.stopPropagation(); close(); });
            overlay.appendChild(closeBtn);

            overlay.addEventListener('click', function(e) { if (e.target === overlay) close(); });
            document.body.appendChild(overlay);
        }

        function close() {
            var lb = document.getElementById('lightbox');
            if (lb) lb.remove();
        }

        function navigate(dir) {
            currentIdx = (currentIdx + dir + urls.length) % urls.length;
            var lb = document.getElementById('lightbox');
            if (!lb) return;
            var img = lb.querySelector('img');
            img.src = urls[currentIdx];
            var counter = lb.querySelector('.lightbox-counter');
            if (counter) counter.textContent = (currentIdx + 1) + ' / ' + urls.length;
        }

        items.forEach(function(el, i) {
            el.addEventListener('click', function() { open(i); });
        });

        document.addEventListener('keydown', function(e) {
            if (!document.getElementById('lightbox')) return;
            if (e.key === 'ArrowLeft') navigate(-1);
            if (e.key === 'ArrowRight') navigate(1);
            if (e.key === 'Escape') close();
        });
    })();

    // ── 14. Escape key to close modals ──────────────
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (authModal && authModal.classList.contains('active')) {
                closeAuthModal();
            }
            var qrModal = document.getElementById('qr-modal');
            if (qrModal && qrModal.classList.contains('active')) {
                qrModal.classList.remove('active');
                document.body.classList.remove('modal-blur');
            }
        }
    });

    // ── 15. Auto-refresh for real-time pages ────────
    // Any element with data-auto-refresh="url" will be polled.
    // When the response hash changes, the page reloads.
    (function() {
        var el = document.querySelector('[data-auto-refresh]');
        if (!el) return;

        var url = el.getAttribute('data-auto-refresh');
        var interval = parseInt(el.getAttribute('data-refresh-interval') || '5000', 10);
        var lastHash = null;

        function poll() {
            fetch(url, { credentials: 'same-origin' })
                .then(function(r) { return r.ok ? r.text() : null; })
                .then(function(text) {
                    if (!text) return;
                    // Simple hash of response to detect changes
                    var hash = text.length + ':' + text.substring(0, 100);
                    if (lastHash !== null && hash !== lastHash) {
                        if (window.BikeSwap && window.BikeSwap.toast) {
                            window.BikeSwap.toast('Stránka byla aktualizována', 'info');
                        }
                        setTimeout(function() { window.location.reload(); }, 1200);
                        return;
                    }
                    lastHash = hash;
                })
                .catch(function() {})
                .finally(function() {
                    setTimeout(poll, interval);
                });
        }

        setTimeout(poll, interval);
    })();

    // ── 16. Flash messages → toast conversion ────────
    (function() {
        var flashContainer = document.querySelector('.flash-container');
        if (!flashContainer) return;
        var alerts = flashContainer.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            // Skip registration success (shown inside auth modal)
            if (alert.id === 'auth-reg-success') return;
            var text = alert.textContent.trim();
            var type = 'info';
            if (alert.classList.contains('alert-success')) type = 'success';
            else if (alert.classList.contains('alert-error')) type = 'error';
            else if (alert.classList.contains('alert-warning')) type = 'warning';
            if (text && window.BikeSwap && window.BikeSwap.toast) {
                window.BikeSwap.toast(text, type);
            }
            alert.remove();
        });
    })();

})();
