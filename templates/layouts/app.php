<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($title) ? e($title) . ' — BikeSwap' : 'BikeSwap' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
</head>
<body class="layout-app">

<div class="app-shell">

  <!-- ── Desktop Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <a href="/">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="16" cy="16" r="12" stroke="#fff" stroke-width="2.2" opacity="0.9"/>
          <path d="M11 13.5h8.5M16.5 10.5l3 3-3 3" stroke="#C9A84C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21 18.5h-8.5M15.5 21.5l-3-3 3-3" stroke="#C9A84C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="navbar-logo-text">Bike<span class="navbar-logo-accent">Swap</span></span>
      </a>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-qr">
        <button type="button" class="sidebar-qr-btn" id="open-qr-scanner">
          <i data-lucide="scan-line"></i> Skenovat QR
        </button>
      </div>

      <?php if (isset($currentUser) && $currentUser->isPolice()): ?>
        <span class="sidebar-nav-label">Policie</span>
        <a href="/admin" class="sidebar-link <?= isActiveRoute('/admin') ?>">
          <i data-lucide="shield"></i> Administrace
        </a>
        <a href="/notifications" class="sidebar-link <?= isActiveRoute('/notifications') ?>" id="notif-link-desktop" style="position:relative">
          <i data-lucide="bell"></i> Oznámení
          <span class="notif-badge" id="notif-count-desktop" style="display:none;position:relative;top:auto;right:auto;margin-left:auto"></span>
        </a>
      <?php else: ?>
        <span class="sidebar-nav-label">Hlavní</span>
        <a href="/dashboard" class="sidebar-link <?= isActiveRoute('/dashboard') ?>">
          <i data-lucide="layout-dashboard"></i> Přehled
        </a>
        <a href="/stolen" class="sidebar-link <?= isActiveRoute('/stolen') ?>">
          <i data-lucide="shield-alert"></i> Odcizená kola
        </a>
        <a href="/shared" class="sidebar-link <?= isActiveRoute('/shared') ?>">
          <i data-lucide="repeat"></i> Sdílená kola
        </a>

        <span class="sidebar-nav-label">Správa</span>
        <a href="/reservations" class="sidebar-link <?= isActiveRoute('/reservations') ?>">
          <i data-lucide="calendar-check"></i> Rezervace
        </a>
        <a href="/notifications" class="sidebar-link <?= isActiveRoute('/notifications') ?>" id="notif-link-desktop" style="position:relative">
          <i data-lucide="bell"></i> Oznámení
          <span class="notif-badge" id="notif-count-desktop" style="display:none;position:relative;top:auto;right:auto;margin-left:auto"></span>
        </a>

        <?php if (isset($currentUser) && $currentUser->isAdmin()): ?>
          <span class="sidebar-nav-label">Admin</span>
          <a href="/admin" class="sidebar-link <?= isActiveRoute('/admin') ?>">
            <i data-lucide="shield"></i> Administrace
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <?php if (isset($currentUser)): ?>
        <a href="/profile" class="sidebar-profile-link">
          <i data-lucide="user"></i> <?= e($currentUser->getName()) ?>
        </a>
      <?php endif; ?>
      <?php if (isset($session)): ?>
        <form method="POST" action="/logout" style="margin:0">
          <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
          <button type="submit" class="sidebar-logout-btn">
            <i data-lucide="log-out"></i> Odhlásit se
          </button>
        </form>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ── Mobile Top Bar ── -->
  <header class="top-bar">
    <a href="/" class="top-bar-logo">
      <svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="16" cy="16" r="12" stroke="#1B4332" stroke-width="2.2"/>
        <path d="M11 13.5h8.5M16.5 10.5l3 3-3 3" stroke="#C9A84C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M21 18.5h-8.5M15.5 21.5l-3-3 3-3" stroke="#C9A84C" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="navbar-logo-text">Bike<span class="navbar-logo-accent">Swap</span></span>
    </a>
    <div class="top-bar-actions">
      <a href="/notifications" class="top-bar-bell" aria-label="Oznámení" style="position:relative">
        <i data-lucide="bell"></i>
        <span class="notif-badge" id="notif-count-mobile" style="display:none"></span>
      </a>
      <a href="/profile" class="top-bar-bell" aria-label="Profil">
        <i data-lucide="user"></i>
      </a>
    </div>
  </header>

  <!-- ── Main Content ── -->
  <main class="page-content">
    <div class="flash-container">
      <?php include __DIR__ . '/../partials/flash.php'; ?>
    </div>

    <?= $content ?? '' ?>
  </main>

  <!-- ── Mobile Bottom Navigation ── -->
  <nav class="bottom-nav">
    <?php if (isset($currentUser) && $currentUser->isPolice()): ?>
      <a href="/admin" class="bottom-nav-item <?= isActiveRoute('/admin') ?>">
        <i data-lucide="shield"></i>
        <span>Admin</span>
      </a>
      <div class="bottom-nav-item bottom-nav-qr-wrap">
        <button type="button" class="bottom-nav-qr-btn" id="open-qr-scanner-mobile" aria-label="Skenovat QR">
          <i data-lucide="camera"></i>
        </button>
      </div>
      <a href="/notifications" class="bottom-nav-item <?= isActiveRoute('/notifications') ?>">
        <i data-lucide="bell"></i>
        <span>Oznámení</span>
      </a>
    <?php else: ?>
      <a href="/dashboard" class="bottom-nav-item <?= isActiveRoute('/dashboard') ?>">
        <i data-lucide="layout-dashboard"></i>
        <span>Přehled</span>
      </a>
      <a href="/stolen" class="bottom-nav-item <?= isActiveRoute('/stolen') ?>">
        <i data-lucide="shield-alert"></i>
        <span>Krádeže</span>
      </a>
      <div class="bottom-nav-item bottom-nav-qr-wrap">
        <button type="button" class="bottom-nav-qr-btn" id="open-qr-scanner-mobile" aria-label="Skenovat QR">
          <i data-lucide="camera"></i>
        </button>
      </div>
      <a href="/reservations" class="bottom-nav-item <?= isActiveRoute('/reservations') ?>">
        <i data-lucide="calendar-check"></i>
        <span>Rezervace</span>
      </a>
      <?php if (isset($currentUser) && $currentUser->isAdmin()): ?>
        <a href="/admin" class="bottom-nav-item <?= isActiveRoute('/admin') ?>">
          <i data-lucide="shield"></i>
          <span>Admin</span>
        </a>
      <?php else: ?>
        <a href="/shared" class="bottom-nav-item <?= isActiveRoute('/shared') ?>">
          <i data-lucide="repeat"></i>
          <span>Sdílená</span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </nav>

</div><!-- .app-shell -->

<!-- ── QR Scanner Modal ── -->
<?php include __DIR__ . '/../partials/qr-modal.php'; ?>

<!-- ── Toast Container ── -->
<div class="toast-container" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/lucide@0.460.0/dist/umd/lucide.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="/js/app.js"></script>

</body>
</html>
