<!-- ── Hero ── -->
<section class="hero">
  <div class="hero-bg" style="background-image:url('https://images.unsplash.com/photo-1507035895480-2b3156c31fc8?w=1920&q=80')"></div>
  <div class="hero-content">
    <div class="hero-badge">
      <i data-lucide="shield-check"></i> Ochrana a sdileni kol
    </div>
    <h1 class="hero-title">
      Chranime vase kolo.<br>
      <span class="hero-title-accent">Sdilime radost z jizdy.</span>
    </h1>
    <p class="hero-subtitle">
      Zaregistrujte sve kolo, ziskejte unikatni QR kod a chranite ho proti kradezi.
      Nebo ho nabidnete k vypujcce ostatnim cyklistum.
    </p>
    <div class="hero-actions">
      <?php if (!isset($session) || !$session->isLoggedIn()): ?>
        <button type="button" class="btn btn-primary btn-lg open-auth-modal" data-tab="register">
          <i data-lucide="user-plus"></i> Zacit zdarma
        </button>
        <button type="button" class="btn btn-secondary btn-lg open-auth-modal" data-tab="login">
          <i data-lucide="log-in"></i> Prihlasit se
        </button>
      <?php else: ?>
        <a href="/dashboard" class="btn btn-primary btn-lg">
          <i data-lucide="layout-dashboard"></i> Moje kola
        </a>
        <a href="/shared" class="btn btn-secondary btn-lg">
          <i data-lucide="repeat"></i> Pujcit si kolo
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ── Features ── -->
<section class="features-section">
  <div class="section-header animate-in">
    <h2>Proc BikeSwap?</h2>
    <p>Komplexni platforma pro ochranu, spravu a sdileni vasich kol.</p>
  </div>

  <div class="features-grid">
    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-teal">
        <i data-lucide="shield-check"></i>
      </div>
      <h3>Ochrana proti kradezi</h3>
      <p>Zaregistrujte kolo a ziskejte QR kod. V pripade kradeze pomuzeme s vyhledavanim.</p>
    </div>

    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-blue">
        <i data-lucide="search"></i>
      </div>
      <h3>Databaze odcizenych kol</h3>
      <p>Prohledavejte verejnou databazi odcizenych kol. Nasli jste podezrele kolo? Nahlaste nalez.</p>
    </div>

    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-copper">
        <i data-lucide="repeat"></i>
      </div>
      <h3>Sdileni kol</h3>
      <p>Nabidnete sve kolo k vypujcce ostatnim nebo si pujcte kolo od jinych uzivatelu.</p>
    </div>

    <div class="feature-card animate-in">
      <div class="feature-icon feature-icon-green">
        <i data-lucide="star"></i>
      </div>
      <h3>Karma system</h3>
      <p>Hodnoceni a karma body zajistuji duveryhodnost a bezpecnost vypujcek.</p>
    </div>
  </div>
</section>

<!-- ── How it works ── -->
<section class="how-it-works">
  <div class="section-header animate-in">
    <h2>Jak to funguje?</h2>
    <p>Tri jednoduche kroky ke kompletni ochrane vaseho kola.</p>
  </div>

  <div class="steps-grid">
    <div class="step-card animate-in">
      <div class="step-number">1</div>
      <div class="step-content">
        <h3>Zaregistrujte se</h3>
        <p>Vytvoreni uctu je zdarma a trva jen minutu. Staci e-mail a heslo.</p>
      </div>
    </div>

    <div class="step-card animate-in">
      <div class="step-number">2</div>
      <div class="step-content">
        <h3>Pridejte sve kolo</h3>
        <p>Vyplnte zakladni udaje, nahrajte fotky a ziskejte unikatni QR kod.</p>
      </div>
    </div>

    <div class="step-card animate-in">
      <div class="step-number">3</div>
      <div class="step-content">
        <h3>Chranite a sdilejte</h3>
        <p>Nalepte QR kod na kolo. Kdokoliv ho naskenuje, muze nahlasit nalez ci si ho pujcit.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-section animate-in">
  <h2>Pripraveni chranit sve kolo?</h2>
  <p>Pridejte se k tisicum cyklistu, kteri uz BikeSwap pouzivaji.</p>
  <?php if (!isset($session) || !$session->isLoggedIn()): ?>
    <button type="button" class="btn btn-primary btn-lg open-auth-modal" data-tab="register">
      <i data-lucide="arrow-right"></i> Zaregistrovat se zdarma
    </button>
  <?php else: ?>
    <a href="/dashboard" class="btn btn-primary btn-lg">
      <i data-lucide="layout-dashboard"></i> Prejit do dashboardu
    </a>
  <?php endif; ?>
</section>
