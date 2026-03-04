<div class="main-public-padded">
  <div class="page-header">
    <div>
      <h1>Odcizená kola</h1>
      <p class="text-muted">Hledáte své ukradené kolo nebo jste nalezli podezřelé kolo? Prohledejte databázi.</p>
    </div>
  </div>

  <!-- Search filters -->
  <form method="GET" action="/stolen">
    <div class="search-bar">
      <input type="text" name="brand"
             value="<?= e($filters['brand'] ?? '') ?>"
             placeholder="Značka (např. Trek, Giant)">
      <input type="text" name="color"
             value="<?= e($filters['color'] ?? '') ?>"
             placeholder="Barva (např. černá)">
      <?php if ($session->isLoggedIn()): ?>
        <input type="text" name="frame_number"
               value="<?= e($filters['frame_number'] ?? '') ?>"
               placeholder="Sériové číslo">
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="search"></i> Hledat
      </button>
      <?php if (!empty($filters)): ?>
        <a href="/stolen" class="btn btn-ghost">
          <i data-lucide="x"></i> Zrušit filtr
        </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Results -->
  <?php if (empty($bikes)): ?>
    <div class="empty-state">
      <i data-lucide="shield-check"></i>
      <?php if (!empty($filters)): ?>
        <h3>Žádná odcizená kola neodpovídají hledání</h3>
        <p>Zkuste změnit nebo zrušit filtr.</p>
      <?php else: ?>
        <h3>Žádná odcizená kola v databázi</h3>
        <p>To je skvělá zpráva! Momentálně nemáme žádná nahlášená odcizená kola.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <p class="text-muted mb-md">Nalezeno <strong><?= count($bikes) ?></strong> odcizených kol.</p>

    <div class="bike-grid">
      <?php foreach ($bikes as $bike): ?>
        <a href="/bike/<?= e($bike->getQrHash()) ?>" class="bike-card card-hover" style="text-decoration:none;color:inherit">
          <div class="bike-card-photo-wrap">
            <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
            <?php if ($primaryPhoto): ?>
              <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
            <?php else: ?>
              <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
            <?php endif; ?>
            <div class="bike-card-badges">
              <span class="status-badge status-stolen">Odcizené</span>
            </div>
          </div>
          <div class="bike-card-body">
            <div class="bike-card-name"><?= e($bike->getFullName()) ?></div>
            <div class="bike-card-meta">
              <i data-lucide="palette"></i> <?= e($bike->getColor()) ?>
            </div>
            <?php if ($bike->getFrameNumber() && $session->isLoggedIn()): ?>
              <div class="bike-card-meta mt-sm">
                <i data-lucide="hash"></i> SČ: <?= e($bike->getFrameNumber()) ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="bike-card-footer">
            <span class="btn btn-sm btn-ghost">Zobrazit detail</span>
            <span class="btn btn-sm btn-success">Nahlásit nález</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
