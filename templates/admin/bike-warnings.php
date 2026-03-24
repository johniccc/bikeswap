<div class="page-header">
  <h1>Upozornění na kola</h1>
  <div class="page-header-actions">
    <a href="/admin/warnings/new" class="btn btn-primary">
      <i data-lucide="plus"></i> Nové upozornění
    </a>
  </div>
</div>

<!-- Tabs -->
<div class="filter-tabs mb-lg">
  <a href="/admin/warnings?tab=active"
     class="filter-tab <?= $tab === 'active' ? 'filter-tab-active' : '' ?>">
    <i data-lucide="alert-triangle" class="icon-inline"></i>
    Aktivní upozornění
    <?php if (!empty($warnings)): ?>
      <span class="badge-count"><?= count($warnings) ?></span>
    <?php endif; ?>
  </a>
  <a href="/admin/warnings?tab=seized"
     class="filter-tab <?= $tab === 'seized' ? 'filter-tab-active' : '' ?>">
    <i data-lucide="truck" class="icon-inline"></i>
    Odvezená kola
    <?php if ($seizedCount > 0): ?>
      <span class="badge-count"><?= $seizedCount ?></span>
    <?php endif; ?>
  </a>
</div>

<?php if ($tab === 'active'): ?>
  <!-- ── Active warnings tab ── -->
  <?php if (empty($warnings)): ?>
    <div class="card">
      <div class="card-body text-center p-lg">
        <i data-lucide="check-circle" style="width:36px;height:36px;color:var(--success);margin:0 auto 0.75rem;display:block"></i>
        <p class="font-bold mb-0">Žádná aktivní upozornění</p>
        <p class="text-muted text-sm">Kompletní historii najdete v detailu každého kola.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <!-- Desktop table -->
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Kolo</th>
              <th>Vlastník</th>
              <th>Místo</th>
              <th>Stáří</th>
              <th>Akce</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($warnings as $w): ?>
              <?php
                $bike = $bikesMap[$w->getBikeId()] ?? null;
                $owner = $bike ? ($ownersMap[$bike->getOwnerId()] ?? null) : null;
                $daysSince = (int) ((time() - strtotime($w->getCreatedAt())) / 86400);
              ?>
              <tr>
                <td>
                  <?php if ($bike): ?>
                    <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
                  <?php else: ?>
                    Kolo #<?= $w->getBikeId() ?>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($owner): ?>
                    <a href="/admin/users/<?= $owner->getId() ?>"><?= e($owner->getFullName()) ?></a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= e($w->getLocationDescription() ?? '—') ?></td>
                <td>
                  <span class="timeline-days-ago <?= $daysSince > 14 ? 'timeline-days-ago-danger' : '' ?>">
                    <?= $daysSince > 0 ? $daysSince . ' d' : 'dnes' ?>
                  </span>
                </td>
                <td>
                  <div class="flex gap-xs">
                    <form method="post" action="/admin/warnings/<?= $w->getId() ?>/resolve">
                      <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                      <button type="submit" class="btn btn-sm btn-success" data-confirm="Opravdu označit jako vyřešené?">
                        <i data-lucide="check"></i> Vyřešit
                      </button>
                    </form>
                    <form method="post" action="/admin/warnings/<?= $w->getBikeId() ?>/seize">
                      <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                      <button type="submit" class="btn btn-sm btn-danger" data-confirm="Opravdu chcete toto kolo odvézt?">
                        <i data-lucide="truck"></i> Odvézt
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile card list -->
      <div class="admin-card-list">
        <?php foreach ($warnings as $w): ?>
          <?php
            $bike = $bikesMap[$w->getBikeId()] ?? null;
            $owner = $bike ? ($ownersMap[$bike->getOwnerId()] ?? null) : null;
            $daysSince = (int) ((time() - strtotime($w->getCreatedAt())) / 86400);
          ?>
          <div class="admin-card-item">
            <div class="admin-card-item-header">
              <span class="admin-card-item-title">
                <?php if ($bike): ?>
                  <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
                <?php else: ?>
                  Kolo #<?= $w->getBikeId() ?>
                <?php endif; ?>
              </span>
              <span class="timeline-days-ago <?= $daysSince > 14 ? 'timeline-days-ago-danger' : '' ?>">
                <?= $daysSince > 0 ? $daysSince . ' d' : 'dnes' ?>
              </span>
            </div>
            <div class="admin-card-item-meta">
              <?php if ($w->getLocationDescription()): ?>
                <span><i data-lucide="map-pin" class="icon-inline"></i> <?= e($w->getLocationDescription()) ?></span>
              <?php endif; ?>
              <?php if ($owner): ?><span>Vlastník: <?= e($owner->getFullName()) ?></span><?php endif; ?>
              <?php if ($w->getReason()): ?><span class="text-muted"><?= e(mb_strimwidth($w->getReason(), 0, 80, '...')) ?></span><?php endif; ?>
            </div>
            <div class="flex gap-xs mt-sm">
              <form method="post" action="/admin/warnings/<?= $w->getId() ?>/resolve">
                <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                <button type="submit" class="btn btn-sm btn-success" data-confirm="Opravdu označit jako vyřešené?">
                  <i data-lucide="check"></i> Vyřešit
                </button>
              </form>
              <form method="post" action="/admin/warnings/<?= $w->getBikeId() ?>/seize">
                <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Opravdu chcete toto kolo odvézt?">
                  <i data-lucide="truck"></i> Odvézt
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

<?php else: ?>
  <!-- ── Seized bikes tab ── -->

  <!-- Search filters -->
  <?php
    $advancedFilters = array_filter([
      $seizedFilters['color'],
      $seizedFilters['year_from'],
      $seizedFilters['year_to'],
      $seizedFilters['frame_number'],
      $seizedFilters['qr_hash'],
    ], fn($v) => $v !== '');
    $advancedCount = count($advancedFilters);
  ?>
  <form method="GET" action="/admin/warnings" class="filter-bar mb-lg<?= $advancedCount > 0 ? ' filters-open' : '' ?>" id="seized-filter-form">
    <input type="hidden" name="tab" value="seized">
    <div class="filter-bar-row">
      <div class="filter-group filter-group-search">
        <input type="text" name="search" placeholder="Hledat podle značky, modelu, popisu..." value="<?= e($seizedFilters['search']) ?>">
      </div>
      <div class="filter-group filter-group-search">
        <input type="text" name="owner" placeholder="Vlastník (jméno, příjmení, e-mail)..." value="<?= e($seizedFilters['owner']) ?>">
      </div>

      <div class="filter-bar-actions">
        <button type="button" class="btn btn-secondary btn-sm filter-toggle-btn" id="seized-filter-toggle" aria-expanded="<?= $advancedCount > 0 ? 'true' : 'false' ?>">
          <i data-lucide="sliders-horizontal"></i>
          Filtry<?php if ($advancedCount > 0): ?> <span class="filter-badge"><?= $advancedCount ?></span><?php endif; ?>
        </button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i data-lucide="search"></i> Hledat
        </button>
        <?php if ($hasSeizedFilters): ?>
          <a href="/admin/warnings?tab=seized" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> Zrušit
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="filter-dropdown">
      <div class="filter-dropdown-inner">
        <div class="filter-group">
          <label for="seized-filter-color">Barva</label>
          <input type="text" id="seized-filter-color" name="color" value="<?= e($seizedFilters['color']) ?>" placeholder="např. červená, modrá...">
        </div>

        <div class="filter-group">
          <label>Rok výroby</label>
          <div class="year-slider-wrap">
            <div class="year-slider-track-wrap">
              <input type="range" id="seized-year-from-range" min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= $seizedFilters['year_from'] ?: $yearMin ?>" class="year-range-input">
              <input type="range" id="seized-year-to-range" min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= $seizedFilters['year_to'] ?: $yearMax ?>" class="year-range-input">
              <div class="year-slider-track"></div>
              <div class="year-slider-fill" id="seized-year-slider-fill"></div>
            </div>
            <div class="year-slider-labels">
              <input type="number" name="year_from" id="seized-year-from-num" class="year-num-input"
                     min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= e($seizedFilters['year_from']) ?>" placeholder="<?= $yearMin ?>">
              <span class="year-slider-sep">–</span>
              <input type="number" name="year_to" id="seized-year-to-num" class="year-num-input"
                     min="<?= $yearMin ?>" max="<?= $yearMax ?>"
                     value="<?= e($seizedFilters['year_to']) ?>" placeholder="<?= $yearMax ?>">
            </div>
          </div>
        </div>

        <div class="filter-group filter-group-frame">
          <label for="seized-frame-number">Sériové číslo</label>
          <input type="text" id="seized-frame-number" name="frame_number"
                 value="<?= e($seizedFilters['frame_number']) ?>" placeholder="Sériové číslo">
        </div>

        <div class="filter-group">
          <label for="seized-qr-hash">ID kola</label>
          <input type="text" id="seized-qr-hash" name="qr_hash"
                 value="<?= e($seizedFilters['qr_hash']) ?>" placeholder="QR hash kola">
        </div>
      </div>
    </div>
  </form>

  <script>
  (function () {
    var form      = document.getElementById('seized-filter-form');
    var toggleBtn = document.getElementById('seized-filter-toggle');
    var fromRange = document.getElementById('seized-year-from-range');
    var toRange   = document.getElementById('seized-year-to-range');
    var fromNum   = document.getElementById('seized-year-from-num');
    var toNum     = document.getElementById('seized-year-to-num');
    var fill      = document.getElementById('seized-year-slider-fill');

    if (!form || !toggleBtn) return;

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

    function raiseThumb(active, other) {
      active.classList.add('thumb-top');
      other.classList.remove('thumb-top');
    }

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

    form.addEventListener('submit', function () {
      var lo = parseInt(fromNum.value, 10);
      var hi = parseInt(toNum.value, 10);
      if (isNaN(lo) || lo <= sliderMin) fromNum.value = '';
      if (isNaN(hi) || hi >= sliderMax) toNum.value = '';
    });

    updateFill();
  }());
  </script>

  <!-- Results -->
  <?php if (empty($seizedBikes)): ?>
    <div class="card">
      <div class="card-body text-center p-lg">
        <?php if ($hasSeizedFilters): ?>
          <i data-lucide="search-x" style="width:36px;height:36px;color:var(--text-muted);margin:0 auto 0.75rem;display:block"></i>
          <p class="font-bold mb-0">Žádná odvezená kola neodpovídají hledání</p>
          <p class="text-muted text-sm">Zkuste změnit nebo zrušit filtr.</p>
        <?php else: ?>
          <p class="text-muted">Žádná odvezená kola.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <p class="text-muted mb-md">
      <?php if ($hasSeizedFilters): ?>
        Nalezeno <strong><?= count($seizedBikes) ?></strong> z <?= $seizedCount ?> odvezených kol.
      <?php else: ?>
        Celkem <strong><?= count($seizedBikes) ?></strong> odvezených kol.
      <?php endif; ?>
    </p>

    <div class="card">
      <!-- Desktop table -->
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Kolo</th>
              <th>Vlastník</th>
              <th>Barva</th>
              <th>Registrováno</th>
              <th>Akce</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($seizedBikes as $sb): ?>
              <?php $owner = $ownersMap[$sb->getOwnerId()] ?? null; ?>
              <tr>
                <td>
                  <div class="flex items-center gap-sm">
                    <?php $photo = $sb->getPrimaryPhoto(); ?>
                    <?php if ($photo): ?>
                      <img src="<?= e($photo->getUrl()) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:var(--radius-sm)">
                    <?php endif; ?>
                    <a href="/bike/<?= e($sb->getQrHash()) ?>"><?= e($sb->getFullName()) ?></a>
                  </div>
                </td>
                <td>
                  <?php if ($owner): ?>
                    <a href="/admin/users/<?= $owner->getId() ?>"><?= e($owner->getFullName()) ?></a>
                    <div class="text-xs text-muted"><?= e($owner->getEmail()) ?></div>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= e($sb->getColor()) ?></td>
                <td class="text-sm text-muted"><?= date('d.m.Y', strtotime($sb->getCreatedAt())) ?></td>
                <td>
                  <div class="flex gap-xs">
                    <a href="/bike/<?= e($sb->getQrHash()) ?>" class="btn btn-sm btn-secondary">
                      <i data-lucide="eye"></i> Detail
                    </a>
                    <form method="post" action="/admin/warnings/<?= $sb->getId() ?>/return">
                      <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                      <button type="submit" class="btn btn-sm btn-success"
                              data-confirm="Opravdu chcete kolo vrátit majiteli?" data-confirm-ok="Ano, vrátit" data-confirm-class="btn-success">
                        <i data-lucide="undo-2"></i> Vrátit
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile card list -->
      <div class="admin-card-list">
        <?php foreach ($seizedBikes as $sb): ?>
          <?php $owner = $ownersMap[$sb->getOwnerId()] ?? null; ?>
          <div class="admin-card-item">
            <div class="admin-card-item-header">
              <span class="admin-card-item-title">
                <a href="/bike/<?= e($sb->getQrHash()) ?>"><?= e($sb->getFullName()) ?></a>
              </span>
              <span class="status-badge status-seized">Odvezeno</span>
            </div>
            <div class="admin-card-item-meta">
              <?php if ($owner): ?>
                <span>Vlastník: <?= e($owner->getFullName()) ?></span>
                <span class="text-muted"><?= e($owner->getEmail()) ?></span>
              <?php endif; ?>
              <span><?= e($sb->getColor()) ?></span>
              <span><?= date('d.m.Y', strtotime($sb->getCreatedAt())) ?></span>
            </div>
            <div class="flex gap-xs mt-sm">
              <a href="/bike/<?= e($sb->getQrHash()) ?>" class="btn btn-sm btn-secondary">
                <i data-lucide="eye"></i> Detail
              </a>
              <form method="post" action="/admin/warnings/<?= $sb->getId() ?>/return">
                <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                <button type="submit" class="btn btn-sm btn-success"
                        data-confirm="Opravdu chcete kolo vrátit majiteli?" data-confirm-ok="Ano, vrátit" data-confirm-class="btn-success">
                  <i data-lucide="undo-2"></i> Vrátit
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
