<!-- ── Hero ── -->
<section class="hero">
  <div class="hero-bg" style="background-image:url('https://images.unsplash.com/photo-1507035895480-2b3156c31fc8?w=1920&q=80')"></div>
  <div class="hero-content">
    <div class="hero-badge">
      <i data-lucide="shield-check"></i> Ochrana a sdílení kol
    </div>
    <h1 class="hero-title">
      Chráníme vaše kolo.<br>
      <span class="hero-title-accent">Sdílíme radost z jízdy.</span>
    </h1>
    <p class="hero-subtitle">
      Zaregistrujte své kolo, získejte unikátní QR kód a chraňte ho proti krádeži.
      Nebo ho nabídněte k výpůjčce ostatním cyklistům.
    </p>
    <div class="hero-actions">
      <?php if (!isset($session) || !$session->isLoggedIn()): ?>
        <button type="button" class="btn btn-primary btn-lg open-auth-modal" data-tab="register">
          <i data-lucide="user-plus"></i> Začít zdarma
        </button>
        <button type="button" class="btn btn-secondary btn-lg open-auth-modal" data-tab="login">
          <i data-lucide="log-in"></i> Přihlásit se
        </button>
      <?php elseif (isset($currentUser) && $currentUser->hasRole('police')): ?>
        <a href="/admin" class="btn btn-primary btn-lg">
          <i data-lucide="shield"></i> Administrace
        </a>
      <?php else: ?>
        <a href="/dashboard" class="btn btn-primary btn-lg">
          <i data-lucide="layout-dashboard"></i> Moje kola
        </a>
        <a href="/shared" class="btn btn-secondary btn-lg">
          <i data-lucide="repeat"></i> Půjčit si kolo
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ── Features ── -->
<section class="features-section">
  <div class="section-header animate-in">
    <h2>Proč BikeSwap?</h2>
    <p>Komplexní platforma pro ochranu, správu a sdílení vašich kol.</p>
  </div>

  <div class="features-grid">
    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-teal">
        <i data-lucide="shield-check"></i>
      </div>
      <h3>Ochrana proti krádeži</h3>
      <p>Zaregistrujte kolo a získejte QR kód. V případě krádeže pomůžeme s vyhledáváním.</p>
    </div>

    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-blue">
        <i data-lucide="search"></i>
      </div>
      <h3>Databáze odcizených kol</h3>
      <p>Prohledávejte veřejnou databázi odcizených kol. Našli jste podezřelé kolo? Nahlaste nález.</p>
    </div>

    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-copper">
        <i data-lucide="repeat"></i>
      </div>
      <h3>Sdílení kol</h3>
      <p>Nabídněte své kolo k výpůjčce ostatním nebo si půjčte kolo od jiných uživatelů.</p>
    </div>

    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-green">
        <i data-lucide="star"></i>
      </div>
      <h3>Karma systém</h3>
      <p>Hodnocení a karma body zajišťují důvěryhodnost a bezpečnost výpůjček.</p>
    </div>
  </div>
</section>

<!-- ── How it works ── -->
<section class="how-it-works">
  <div class="section-header animate-in">
    <h2>Jak to funguje?</h2>
    <p>Tři jednoduché kroky ke kompletní ochraně vašeho kola.</p>
  </div>

  <div class="steps-grid">
    <div class="step-card animate-in">
      <div class="step-number">1</div>
      <div class="step-content">
        <h3>Zaregistrujte se</h3>
        <p>Vytvoření účtu je zdarma a trvá jen minutu. Stačí e-mail a heslo.</p>
      </div>
    </div>

    <div class="step-card animate-in">
      <div class="step-number">2</div>
      <div class="step-content">
        <h3>Přidejte své kolo</h3>
        <p>Vyplňte základní údaje, nahrajte fotky a získejte unikátní QR kód.</p>
      </div>
    </div>

    <div class="step-card animate-in">
      <div class="step-number">3</div>
      <div class="step-content">
        <h3>Chraňte a sdílejte</h3>
        <p>Nalepte QR kód na kolo. Kdokoliv ho naskenuje, může nahlásit nález či si ho půjčit.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-section animate-in">
  <h2>Připraveni chránit své kolo?</h2>
  <p>Přidejte se k tisícům cyklistů, kteří už BikeSwap používají.</p>
  <?php if (!isset($session) || !$session->isLoggedIn()): ?>
    <button type="button" class="btn btn-primary btn-lg open-auth-modal" data-tab="register">
      <i data-lucide="arrow-right"></i> Zaregistrovat se zdarma
    </button>
  <?php elseif (isset($currentUser) && $currentUser->hasRole('police')): ?>
    <a href="/admin" class="btn btn-primary btn-lg">
      <i data-lucide="shield"></i> Přejít do administrace
    </a>
  <?php else: ?>
    <a href="/dashboard" class="btn btn-primary btn-lg">
      <i data-lucide="layout-dashboard"></i> Přejít do dashboardu
    </a>
  <?php endif; ?>
</section>
