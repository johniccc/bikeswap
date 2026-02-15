<div class="hero">
    <h1>BikeSwap</h1>
    <p>Registrujte své kolo, chraňte ho proti krádeži, sdílejte s ostatními</p>
    <?php if (!isset($session) || !$session->isLoggedIn()): ?>
        <a href="/register" class="btn btn-primary btn-large">Začít zdarma</a>
    <?php else: ?>
        <a href="/dashboard" class="btn btn-primary btn-large">Moje kola</a>
    <?php endif; ?>
</div>

<div class="features">
    <div class="feature-card">
        <div class="feature-icon">🔒</div>
        <h3>Ochrana proti krádeži</h3>
        <p>Zaregistrujte kolo a získejte QR kód. V případě krádeže pomůžeme s vyhledáváním.</p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h3>Databáze odcizených kol</h3>
        <p>Prohledávejte veřejnou databázi odcizených kol. Našli jste podezřelé kolo? Nahlaste nález.</p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">🚴</div>
        <h3>Sdílení kol</h3>
        <p>Nabídněte své kolo k výpůjčce ostatním nebo si půjčte kolo od jiných uživatelů.</p>
    </div>

    <div class="feature-card">
        <div class="feature-icon">⭐</div>
        <h3>Karma systém</h3>
        <p>Hodnocení a karma body zajišťují důvěryhodnost a bezpečnost výpůjček.</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2>Jak to funguje?</h2>
        <ol>
            <li><strong>Zaregistrujte se</strong> – Vytvoření účtu je zdarma a trvá jen minutu</li>
            <li><strong>Přidejte své kolo</strong> – Vyplňte základní údaje a přidejte fotografie</li>
            <li><strong>Získejte QR kód</strong> – Vytiskněte a umístěte na kolo</li>
            <li><strong>Sdílejte nebo chraňte</strong> – Nabídněte kolo k výpůjčce nebo ho chraňte proti krádeži</li>
        </ol>
    </div>
</div>