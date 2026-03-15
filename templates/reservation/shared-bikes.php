<div class="main-public-padded">
  <div class="page-header">
    <div>
      <h1>Sdílená kola k výpůjčce</h1>
      <p class="text-muted">Prohlížejte kola nabízená k výpůjčce od ostatních uživatelů.</p>
    </div>
  </div>

  <!-- Filter bar -->
  <?php
    $advancedFilters = array_filter([
      $filters['color'],
      $filters['year_from'],
      $filters['year_to'],
      $filters['date_from'],
      $filters['date_to'],
    ], fn($v) => $v !== '');
    $advancedCount = count($advancedFilters);
  ?>
  <form method="GET" action="/shared" class="filter-bar mb-lg<?= $advancedCount > 0 ? ' filters-open' : '' ?>" id="shared-filter-form">
    <div class="filter-bar-row">
      <div class="filter-group filter-group-search">
        <input type="text" id="filter-search" name="search" placeholder="Hledat podle značky, modelu..." value="<?= e($filters['search']) ?>">
      </div>

      <div class="filter-bar-actions">
        <button type="button" class="btn btn-secondary btn-sm filter-toggle-btn" id="filter-toggle" aria-expanded="<?= $advancedCount > 0 ? 'true' : 'false' ?>">
          <i data-lucide="sliders-horizontal"></i>
          Filtry<?php if ($advancedCount > 0): ?> <span class="filter-badge"><?= $advancedCount ?></span><?php endif; ?>
        </button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i data-lucide="search"></i> Hledat
        </button>
        <?php if ($hasFilters): ?>
          <a href="/shared" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> Zrušit
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="filter-dropdown">
      <div class="filter-dropdown-inner">
        <div class="filter-group">
          <label for="filter-color">Barva</label>
          <input type="text" id="filter-color" name="color" value="<?= e($filters['color']) ?>" placeholder="např. červená, modrá...">
        </div>

        <div class="filter-group">
          <label>Rok výroby</label>
          <div class="year-slider-wrap">
            <div class="year-slider-track-wrap">
              <input type="range" id="year-from-range" min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= $filters['year_from'] ?: $yearMin ?>" class="year-range-input">
              <input type="range" id="year-to-range" min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= $filters['year_to'] ?: $yearMax ?>" class="year-range-input">
              <div class="year-slider-track"></div>
              <div class="year-slider-fill" id="year-slider-fill"></div>
            </div>
            <div class="year-slider-labels">
              <input type="number" name="year_from" id="year-from-num" class="year-num-input"
                     min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= e($filters['year_from']) ?>" placeholder="<?= $yearMin ?>">
              <span class="year-slider-sep">–</span>
              <input type="number" name="year_to" id="year-to-num" class="year-num-input"
                     min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= e($filters['year_to']) ?>" placeholder="<?= $yearMax ?>">
            </div>
          </div>
        </div>

        <div class="filter-group">
          <label>Dostupnost</label>
          <div class="input-range">
            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>" min="<?= date('Y-m-d') ?>">
            <span>–</span>
            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>" min="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
  (function () {
    var form      = document.getElementById('shared-filter-form');
    var toggleBtn = document.getElementById('filter-toggle');
    var fromRange = document.getElementById('year-from-range');
    var toRange   = document.getElementById('year-to-range');
    var fromNum   = document.getElementById('year-from-num');
    var toNum     = document.getElementById('year-to-num');
    var fill      = document.getElementById('year-slider-fill');

    if (!form || !toggleBtn) return;

    // Toggle dropdown
    toggleBtn.addEventListener('click', function () {
      form.classList.toggle('filters-open');
      toggleBtn.setAttribute('aria-expanded', form.classList.contains('filters-open') ? 'true' : 'false');
    });

    if (!fromRange || !toRange) return;

    var sliderMin = parseInt(fromRange.min, 10);
    var sliderMax = parseInt(fromRange.max, 10);

    function clamp(v, lo, hi) { return Math.max(lo, Math.min(v, hi)); }

    function updateFill() {
      if (!fill) return;
      var lo  = parseInt(fromRange.value, 10);
      var hi  = parseInt(toRange.value, 10);
      var rng = sliderMax - sliderMin;
      var pLo = rng > 0 ? ((lo - sliderMin) / rng) * 100 : 0;
      var pHi = rng > 0 ? ((hi - sliderMin) / rng) * 100 : 100;
      fill.style.left  = pLo + '%';
      fill.style.width = Math.max(0, pHi - pLo) + '%';
    }

    // Raise whichever thumb was last touched so it stays on top and is grabbable
    function raiseThumb(active, other) {
      active.classList.add('thumb-top');
      other.classList.remove('thumb-top');
    }

    // Initially "from" thumb is on top (left end)
    fromRange.classList.add('thumb-top');

    fromRange.addEventListener('mousedown',  function () { raiseThumb(fromRange, toRange); });
    fromRange.addEventListener('touchstart', function () { raiseThumb(fromRange, toRange); }, { passive: true });
    toRange.addEventListener('mousedown',    function () { raiseThumb(toRange, fromRange); });
    toRange.addEventListener('touchstart',   function () { raiseThumb(toRange, fromRange); }, { passive: true });

    fromRange.addEventListener('input', function () {
      var lo = parseInt(fromRange.value, 10);
      var hi = parseInt(toRange.value, 10);
      if (lo > hi) { lo = hi; fromRange.value = lo; }
      fromNum.value = lo;
      updateFill();
    });

    toRange.addEventListener('input', function () {
      var lo = parseInt(fromRange.value, 10);
      var hi = parseInt(toRange.value, 10);
      if (hi < lo) { hi = lo; toRange.value = hi; }
      toNum.value = hi;
      updateFill();
    });

    fromNum.addEventListener('input', function () {
      var lo = parseInt(fromNum.value, 10);
      if (!isNaN(lo)) {
        lo = clamp(lo, sliderMin, sliderMax);
        if (lo > parseInt(toRange.value, 10)) { toRange.value = lo; toNum.value = lo; }
        fromRange.value = lo;
        updateFill();
      }
    });

    toNum.addEventListener('input', function () {
      var hi = parseInt(toNum.value, 10);
      if (!isNaN(hi)) {
        hi = clamp(hi, sliderMin, sliderMax);
        if (hi < parseInt(fromRange.value, 10)) { fromRange.value = hi; fromNum.value = hi; }
        toRange.value = hi;
        updateFill();
      }
    });

    // On submit: clear year inputs if equal to full range (= no real filter intended)
    form.addEventListener('submit', function () {
      var lo = parseInt(fromNum.value, 10);
      var hi = parseInt(toNum.value, 10);
      if (isNaN(lo) || lo <= sliderMin) fromNum.value = '';
      if (isNaN(hi) || hi >= sliderMax) toNum.value = '';
    });

    updateFill();
  }());
  </script>

  <?php if (empty($bikes)): ?>
    <div class="empty-state">
      <?php if ($hasFilters): ?>
        <i data-lucide="search-x"></i>
        <h3>Žádná kola neodpovídají filtru</h3>
        <p>Zkuste změnit nebo zrušit filtry.</p>
        <a href="/shared" class="btn btn-secondary btn-sm mt-sm">Zrušit filtry</a>
      <?php else: ?>
        <i data-lucide="repeat"></i>
        <h3>Žádná kola momentálně nejsou k dispozici</h3>
        <p>Momentálně nikdo nenabízí své kolo k výpůjčce. Zkuste to později.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="bike-grid">
      <?php foreach ($bikes as $bike): ?>
        <?php
          $isLoggedIn = isset($currentUser) && $currentUser !== null;
          $isOwner = $isLoggedIn && $bike->isOwnedBy($currentUser->getId());
          $activeRes = $activeReservations[$bike->getId()] ?? null;
        ?>
        <div class="bike-card card-hover" data-href="/bike/<?= e($bike->getQrHash()) ?>" style="cursor:pointer">
          <div class="bike-card-photo-wrap">
            <?php $photo = $bike->getPrimaryPhoto(); ?>
            <?php if ($photo): ?>
              <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
            <?php else: ?>
              <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
            <?php endif; ?>
            <?php if ($activeRes): ?>
              <div class="bike-card-badges">
                <span class="status-badge" style="background:var(--warning-light,#fef3c7);color:var(--warning,#d97706);border:1px solid var(--warning,#d97706)">
                  <i data-lucide="calendar" style="width:11px;height:11px;display:inline;vertical-align:-1px"></i>
                  <?= e(date('d.m.', strtotime($activeRes['date_from']))) ?> – <?= e(date('d.m.', strtotime($activeRes['date_to']))) ?>
                </span>
              </div>
            <?php endif; ?>
          </div>

          <div class="bike-card-body">
            <div class="bike-card-name"><?= e($bike->getFullName()) ?></div>
            <div class="bike-card-meta">
              <i data-lucide="palette"></i> <?= e($bike->getColor()) ?>
              <?php if ($bike->getYearOfManufacture()): ?>
                &middot; <?= $bike->getYearOfManufacture() ?>
              <?php endif; ?>
            </div>

            <?php if ($bike->getDescription()): ?>
              <p class="text-muted text-sm mt-sm"><?= e(mb_substr($bike->getDescription(), 0, 100)) ?><?= mb_strlen($bike->getDescription()) > 100 ? '...' : '' ?></p>
            <?php endif; ?>
          </div>

          <div class="bike-card-footer">
            <?php if ($isLoggedIn && !$isOwner): ?>
              <a href="/reservation/new/<?= $bike->getId() ?>" class="btn btn-sm btn-primary">
                <i data-lucide="calendar-plus"></i> Rezervovat
              </a>
            <?php elseif ($isOwner): ?>
              <a href="/reservations?bike=<?= $bike->getId() ?>" class="btn btn-sm btn-secondary">
                <i data-lucide="list"></i> Moje rezervace
              </a>
            <?php elseif (!$isLoggedIn): ?>
              <a href="/login?redirect=<?= urlencode('/bike/' . $bike->getQrHash()) ?>" class="btn btn-sm btn-primary">
                <i data-lucide="log-in"></i> Přihlásit a rezervovat
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
