<!DOCTYPE html>
<html lang="cs" id="html-root">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($title) ? e($title) . ' — BikeSwap' : 'BikeSwap' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app-shell">

  <!-- MOBILE TOP BAR -->
  <header class="top-bar">
    <a href="/" class="top-bar-logo">Bike<span>Swap</span></a>
    <div class="top-bar-actions">
      <button type="button" class="dark-mode-toggle" id="dark-toggle" aria-label="Přepnout tmavý mód">🌙</button>
      <?php if (isset($session) && $session->isLoggedIn()): ?>
      <a href="/notifications" class="top-bar-bell" id="notif-bell-mobile" aria-label="Oznámení">
        <span aria-hidden="true">🔔</span><span class="notif-badge" id="notif-count-mobile" style="display:none"></span>
      </a>
      <?php endif; ?>
    </div>
  </header>

  <!-- DESKTOP SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <a href="/">Bike<span>Swap</span></a>
    </div>
    <nav class="sidebar-nav">
      <a href="/" class="sidebar-nav-link <?= isActiveRoute('/') ?>"><span aria-hidden="true">🏠</span> Domů</a>
      <a href="/bike/public" class="sidebar-nav-link <?= isActiveRoute('/bike/public') ?>"><span aria-hidden="true">🌍</span> Veřejná kola</a>
      <a href="/shared" class="sidebar-nav-link <?= isActiveRoute('/shared') ?>"><span aria-hidden="true">🔄</span> Sdílená kola</a>
      <?php if (isset($session) && $session->isLoggedIn()): ?>
      <a href="/dashboard" class="sidebar-nav-link <?= isActiveRoute('/dashboard') ?>"><span aria-hidden="true">📊</span> Přehled</a>
      <a href="/bike/my" class="sidebar-nav-link <?= isActiveRoute('/bike/my') ?>"><span aria-hidden="true">🚲</span> Moje kola</a>
      <a href="/reservations" class="sidebar-nav-link <?= isActiveRoute('/reservations') ?>"><span aria-hidden="true">📅</span> Rezervace</a>
      <a href="/notifications" class="sidebar-nav-link <?= isActiveRoute('/notifications') ?>" id="notif-link-desktop">
        <span aria-hidden="true">🔔</span> Oznámení<span class="notif-badge" id="notif-count-desktop" style="display:none"></span>
      </a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-qr">
      <button type="button" class="sidebar-qr-btn" id="open-qr-scanner"><span aria-hidden="true">📷</span> Skenovat QR</button>
    </div>
    <div class="sidebar-footer">
      <?php if (isset($session) && $session->isLoggedIn()): ?>
      <a href="/profile" class="sidebar-profile-link"><span aria-hidden="true">👤</span> <?= isset($currentUser) ? e($currentUser->getName()) : '' ?></a>
      <form method="POST" action="/logout">
        <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <button type="submit" class="sidebar-logout-btn">Odhlásit se</button>
      </form>
      <?php else: ?>
      <a href="/login" class="btn btn-primary btn-sm" style="width:100%;text-align:center;">Přihlásit se</a>
      <?php endif; ?>
      <button type="button" class="dark-mode-toggle" id="dark-toggle-desktop" style="margin:0.5rem 0.75rem;width:calc(100% - 1.5rem);" aria-label="Přepnout tmavý mód">🌙 Tmavý mód</button>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="page-content">
    <?php if (isset($session) && ($flashSuccess = $session->getFlash('success'))): ?>
    <div class="alert alert-success"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (isset($session) && ($flashError = $session->getFlash('error'))): ?>
    <div class="alert alert-error"><?= e($flashError) ?></div>
    <?php endif; ?>
    <?php if (isset($session) && ($flashInfo = $session->getFlash('info'))): ?>
    <div class="alert alert-info"><?= e($flashInfo) ?></div>
    <?php endif; ?>

    <?= $content ?? '' /* pre-rendered template HTML — must not be escaped */ ?>
  </main>

  <!-- MOBILE BOTTOM NAV -->
  <nav class="bottom-nav">
    <a href="/" class="bottom-nav-item <?= isActiveRoute('/') ?>">
      <span class="bottom-nav-icon" aria-hidden="true">🏠</span>
      <span class="bottom-nav-label">Domů</span>
    </a>
    <a href="/bike/public" class="bottom-nav-item <?= isActiveRoute('/bike/public') ?>">
      <span class="bottom-nav-icon" aria-hidden="true">🌍</span>
      <span class="bottom-nav-label">Veřejná</span>
    </a>
    <div class="bottom-nav-item bottom-nav-qr-wrap">
      <button type="button" class="bottom-nav-qr-btn" id="open-qr-scanner-mobile" aria-label="Skenovat QR"><span aria-hidden="true">📷</span></button>
    </div>
    <a href="/shared" class="bottom-nav-item <?= isActiveRoute('/shared') ?>">
      <span class="bottom-nav-icon" aria-hidden="true">🔄</span>
      <span class="bottom-nav-label">Sdílená</span>
    </a>
    <?php if (isset($session) && $session->isLoggedIn()): ?>
    <a href="/profile" class="bottom-nav-item <?= isActiveRoute('/profile') ?>">
      <span class="bottom-nav-icon" aria-hidden="true">👤</span>
      <span class="bottom-nav-label">Profil</span>
    </a>
    <?php else: ?>
    <a href="/login" class="bottom-nav-item <?= isActiveRoute('/login') ?>">
      <span class="bottom-nav-icon" aria-hidden="true">🔑</span>
      <span class="bottom-nav-label">Přihlásit</span>
    </a>
    <?php endif; ?>
  </nav>

</div><!-- .app-shell -->

<!-- QR SCANNER MODAL -->
<div class="modal-overlay" id="qr-modal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h2 class="modal-title">Skenovat QR kód</h2>
      <button type="button" class="modal-close" id="close-qr-modal" aria-label="Zavřít">×</button>
    </div>
    <div class="modal-body">
      <video id="qr-video" style="width:100%;border-radius:8px;background:#000" playsinline></video>
      <canvas id="qr-canvas" style="display:none"></canvas>
      <p id="qr-status" style="text-align:center;margin-top:.75rem;color:var(--color-text-muted)">Spouštím kameru…</p>
      <div style="text-align:center;margin-top:1rem">
        <label class="btn btn-secondary btn-sm" style="cursor:pointer">
          📁 Nahrát obrázek
          <input type="file" id="qr-file-input" accept="image/*" capture="environment" style="display:none">
        </label>
      </div>
    </div>
  </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<script>
(function() {
    'use strict';
    var html = document.getElementById('html-root') || document.documentElement;
    var stored = localStorage.getItem('bikeswap-theme');
    if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.setAttribute('data-theme', 'dark');
    }

    function toggleDark() {
        var isDark = html.getAttribute('data-theme') === 'dark';
        if (isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('bikeswap-theme', 'light');
            document.querySelectorAll('.dark-mode-toggle').forEach(function(b) { b.textContent = b.textContent.includes('mód') ? '🌙 Tmavý mód' : '🌙'; });
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('bikeswap-theme', 'dark');
            document.querySelectorAll('.dark-mode-toggle').forEach(function(b) { b.textContent = b.textContent.includes('mód') ? '☀ Světlý mód' : '☀'; });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var isDark = html.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('.dark-mode-toggle').forEach(function(btn) {
            btn.textContent = btn.textContent.includes('mód') ? (isDark ? '☀ Světlý mód' : '🌙 Tmavý mód') : (isDark ? '☀' : '🌙');
            btn.addEventListener('click', toggleDark);
        });
    });
})();
</script>

<!-- jsQR -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="/js/app.js"></script>

<?php if (isset($session) && $session->isLoggedIn()): ?>
<script>
(function() {
  'use strict';
  var lastCount = 0;
  function pollNotifications() {
    fetch('/notifications/count', { credentials: 'same-origin' })
      .then(function(r) { return r.ok ? r.json() : null; })
      .then(function(data) {
        if (!data) return;
        var count = data.unread_count || 0;
        var mobileEl = document.getElementById('notif-count-mobile');
        var desktopEl = document.getElementById('notif-count-desktop');
        if (mobileEl) {
          mobileEl.style.display = count > 0 ? '' : 'none';
          mobileEl.textContent = count > 99 ? '99+' : String(count);
        }
        if (desktopEl) {
          desktopEl.style.display = count > 0 ? '' : 'none';
          desktopEl.textContent = count > 99 ? '99+' : String(count);
        }
        if (count > lastCount) {
          showToast('Máte nová oznámení');
        }
        lastCount = count;
      })
      .catch(function() {});
    setTimeout(pollNotifications, 30000);
  }
  function showToast(msg) {
    var tc = document.getElementById('toast-container');
    if (!tc) return;
    var t = document.createElement('div');
    t.className = 'toast toast-info';
    t.textContent = msg;
    tc.appendChild(t);
    setTimeout(function() { t.remove(); }, 4000);
  }
  pollNotifications();
})();
</script>
<?php endif; ?>

</body>
</html>
