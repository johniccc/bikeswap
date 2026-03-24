<div class="main-public-padded">
  <div class="page-header">
    <div>
      <h1>Odcizená kola</h1>
      <p class="text-muted">Hledáte své ukradené kolo nebo jste nalezli podezřelé kolo? Prohledejte databázi.</p>
    </div>
  </div>

  <!-- Search filters -->
  <?php
    $advancedFilters = array_filter([
      $filters['color'],
      $filters['year_from'],
      $filters['year_to'],
      $filters['frame_number'],
      $filters['qr_hash'],
    ], fn($v) => $v !== '');
    $advancedCount = count($advancedFilters);
  ?>
  <form method="GET" action="/stolen" class="filter-bar mb-lg<?= $advancedCount > 0 ? ' filters-open' : '' ?>" id="stolen-filter-form">
    <div class="filter-bar-row">
      <div class="filter-group filter-group-search">
        <input type="text" name="search" placeholder="Hledat podle značky, modelu, popisu..." value="<?= e($filters['search']) ?>">
      </div>

      <div class="filter-bar-actions">
        <button type="button" class="btn btn-secondary btn-sm filter-toggle-btn" id="stolen-filter-toggle" aria-expanded="<?= $advancedCount > 0 ? 'true' : 'false' ?>">
          <i data-lucide="sliders-horizontal"></i>
          Filtry<?php if ($advancedCount > 0): ?> <span class="filter-badge"><?= $advancedCount ?></span><?php endif; ?>
        </button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i data-lucide="search"></i> Hledat
        </button>
        <?php if ($hasFilters): ?>
          <a href="/stolen" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> Zrušit
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="filter-dropdown">
      <div class="filter-dropdown-inner">
        <div class="filter-group">
          <label for="stolen-filter-color">Barva</label>
          <input type="text" id="stolen-filter-color" name="color" value="<?= e($filters['color']) ?>" placeholder="např. červená, modrá...">
        </div>

        <div class="filter-group">
          <label>Rok výroby</label>
          <div class="year-slider-wrap">
            <div class="year-slider-track-wrap">
              <input type="range" id="stolen-year-from-range" min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= $filters['year_from'] ?: $yearMin ?>" class="year-range-input">
              <input type="range" id="stolen-year-to-range" min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= $filters['year_to'] ?: $yearMax ?>" class="year-range-input">
              <div class="year-slider-track"></div>
              <div class="year-slider-fill" id="stolen-year-slider-fill"></div>
            </div>
            <div class="year-slider-labels">
              <input type="number" name="year_from" id="stolen-year-from-num" class="year-num-input"
                     min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= e($filters['year_from']) ?>" placeholder="<?= $yearMin ?>">
              <span class="year-slider-sep">–</span>
              <input type="number" name="year_to" id="stolen-year-to-num" class="year-num-input"
                     min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= e($filters['year_to']) ?>" placeholder="<?= $yearMax ?>">
            </div>
          </div>
        </div>

        <div class="filter-group filter-group-frame">
          <label for="stolen-frame-number">Sériové číslo</label>
          <input type="text" id="stolen-frame-number" name="frame_number"
                 value="<?= e($filters['frame_number']) ?>" placeholder="Sériové číslo">
        </div>

        <div class="filter-group">
          <label for="stolen-qr-hash">ID kola</label>
          <input type="text" id="stolen-qr-hash" name="qr_hash"
                 value="<?= e($filters['qr_hash']) ?>" placeholder="QR hash kola">
        </div>
      </div>
    </div>
  </form>

  <script src="/js/filter-slider.js"></script>

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
        <div class="bike-card card-hover" data-href="/bike/<?= e($bike->getQrHash()) ?>" style="cursor:pointer">
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
            <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-sm btn-ghost">Zobrazit detail</a>
            <a href="/found/report/<?= e($bike->getQrHash()) ?>" class="btn btn-sm btn-success">Nahlásit nález</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
