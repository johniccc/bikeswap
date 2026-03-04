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
<body class="layout-public">

  <!-- ── Navbar ── -->
  <header class="navbar">
    <div class="navbar-inner">
      <a href="/" class="navbar-logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="16" cy="16" r="13" stroke="#1B4332" stroke-width="2.5"/>
          <circle cx="16" cy="16" r="2.5" fill="#1B4332"/>
          <line x1="16" y1="5" x2="16" y2="13.5" stroke="#1B4332" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="16" y1="18.5" x2="16" y2="27" stroke="#1B4332" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="5" y1="16" x2="13.5" y2="16" stroke="#1B4332" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="18.5" y1="16" x2="27" y2="16" stroke="#1B4332" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M10.5 12.5L15 16L10.5 19.5" stroke="#C9A84C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21.5 12.5L17 16L21.5 19.5" stroke="#C9A84C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Bike<span class="navbar-logo-accent">Swap</span>
      </a>

      <nav class="navbar-links">
        <a href="/stolen" class="<?= isActiveRoute('/stolen') ?>">
          <i data-lucide="shield-alert"></i> Odcizená kola
        </a>
        <a href="/shared" class="<?= isActiveRoute('/shared') ?>">
          <i data-lucide="repeat"></i> Sdílená kola
        </a>
      </nav>

      <div class="navbar-actions">
        <button type="button" class="btn btn-secondary btn-sm" id="open-qr-scanner">
          <i data-lucide="scan-line"></i> Skenovat QR
        </button>

        <?php if (isset($session) && $session->isLoggedIn()): ?>
          <a href="/notifications" class="btn btn-ghost btn-sm" style="position:relative" aria-label="Oznámení">
            <i data-lucide="bell"></i>
            <span class="notif-badge" id="notif-count-public" style="display:none"></span>
          </a>
          <a href="/dashboard" class="btn btn-primary btn-sm">
            <i data-lucide="layout-dashboard"></i> Dashboard
          </a>
        <?php else: ?>
          <button type="button" class="btn btn-ghost btn-sm open-auth-modal" data-tab="login">
            Přihlásit se
          </button>
          <button type="button" class="btn btn-primary btn-sm open-auth-modal" data-tab="register">
            Registrace
          </button>
        <?php endif; ?>
      </div>

      <button type="button" class="navbar-burger" id="burger-toggle" aria-label="Menu">
        <i data-lucide="menu" id="burger-icon"></i>
      </button>
    </div>

    <!-- Mobile Menu -->
    <div class="navbar-mobile-menu" id="mobile-menu">
      <?php if (isset($session) && $session->isLoggedIn()): ?>
        <a href="/dashboard"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <?php endif; ?>
      <a href="/stolen"><i data-lucide="shield-alert"></i> Odcizená kola</a>
      <a href="/shared"><i data-lucide="repeat"></i> Sdílená kola</a>
      <div class="navbar-mobile-divider"></div>
      <button type="button" id="open-qr-scanner-mobile"><i data-lucide="scan-line"></i> Skenovat QR kód</button>
      <div class="navbar-mobile-divider"></div>
      <?php if (isset($session) && $session->isLoggedIn()): ?>
        <a href="/notifications"><i data-lucide="bell"></i> Oznámení</a>
        <a href="/profile"><i data-lucide="user"></i> Profil</a>
      <?php else: ?>
        <button type="button" class="open-auth-modal" data-tab="login"><i data-lucide="log-in"></i> Přihlásit se</button>
        <button type="button" class="open-auth-modal" data-tab="register"><i data-lucide="user-plus"></i> Registrace</button>
      <?php endif; ?>
    </div>
  </header>

  <!-- ── Flash Messages ── -->
  <div class="main-public">
    <div class="flash-container" style="max-width:var(--content-max);margin:0 auto;padding:1rem 1.5rem 0;">
      <?php include __DIR__ . '/../partials/flash.php'; ?>
    </div>

    <!-- ── Main Content ── -->
    <?= $content ?? '' ?>
  </div>

  <!-- ── Footer ── -->
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-logo">
        <svg width="24" height="24" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="16" cy="16" r="13" stroke="currentColor" stroke-width="2.5"/>
          <circle cx="16" cy="16" r="2.5" fill="currentColor"/>
          <line x1="16" y1="5" x2="16" y2="13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="16" y1="18.5" x2="16" y2="27" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="5" y1="16" x2="13.5" y2="16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="18.5" y1="16" x2="27" y2="16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M10.5 12.5L15 16L10.5 19.5" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21.5 12.5L17 16L21.5 19.5" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Bike<span class="footer-logo-accent">Swap</span>
      </div>
      <div class="footer-links">
        <a href="/stolen">Odcizená kola</a>
        <a href="/shared">Sdílená kola</a>
      </div>
      <div class="footer-text">&copy; <?= date('Y') ?> BikeSwap. Všechna práva vyhrazena.</div>
    </div>
  </footer>

  <!-- ── Auth Modal ── -->
  <?php include __DIR__ . '/../partials/auth-modal.php'; ?>

  <!-- ── QR Scanner Modal ── -->
  <?php include __DIR__ . '/../partials/qr-modal.php'; ?>

  <!-- ── Toast Container ── -->
  <div class="toast-container" id="toast-container"></div>

  <script src="https://cdn.jsdelivr.net/npm/lucide@0.460.0/dist/umd/lucide.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
  <script src="/js/app.js"></script>


</body>
</html>
