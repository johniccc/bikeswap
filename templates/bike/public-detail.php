<div class="main-public-padded">
  <div class="bike-detail">

    <!-- Photo gallery -->
    <?php $photos = $bike->getPhotos(); ?>
    <?php if (!empty($photos)): ?>
      <div class="bike-gallery-grid">
        <?php foreach ($photos as $photo): ?>
          <div class="bike-gallery-item">
            <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Stolen alert -->
    <?php if ($bike->isStolen()): ?>
      <div class="alert alert-stolen mb-lg">
        <i data-lucide="alert-triangle"></i>
        <div>
          <strong>Toto kolo je hlaseno jako odcizene!</strong>
          <?php if ($theftReport): ?>
            <p class="mt-sm" style="font-weight:400">
              Datum kradeze: <?= e($theftReport->getFormattedTheftDate()) ?>
              <?php if ($theftReport->getTheftLocationText()): ?>
                — <?= e($theftReport->getTheftLocationText()) ?>
              <?php endif; ?>
            </p>
          <?php endif; ?>
          <?php if (!$isOwner): ?>
            <p class="mt-sm" style="font-weight:400">
              Poznavate toto kolo nebo jste ho nasli? Pomozte majiteli nahlasenim nalezu.
            </p>
            <a href="/found/report/<?= e($bike->getQrHash()) ?>" class="btn btn-success mt-sm">
              <i data-lucide="map-pin"></i> Nahlasit nalez tohoto kola
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Bike info -->
    <div class="bike-info-grid">
      <div>
        <h1><?= e($bike->getFullName()) ?></h1>

        <div class="flex gap-sm mt-sm mb-lg">
          <?php
            $statusLabels = [
              'active' => 'Aktivni',
              'stolen' => 'Odcizene',
              'shared' => 'Sdilene',
              'inactive' => 'Neaktivni',
            ];
          ?>
          <span class="status-badge status-<?= e($bike->getStatus()) ?>">
            <?= e($statusLabels[$bike->getStatus()] ?? $bike->getStatus()) ?>
          </span>
          <?php if ($bike->isShared()): ?>
            <span class="status-badge status-shared">Sdilene</span>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="info-row">
              <span class="info-label">Znacka</span>
              <span class="info-value"><?= e($bike->getBrand()) ?></span>
            </div>
            <?php if ($bike->getModel()): ?>
              <div class="info-row">
                <span class="info-label">Model</span>
                <span class="info-value"><?= e($bike->getModel()) ?></span>
              </div>
            <?php endif; ?>
            <div class="info-row">
              <span class="info-label">Barva</span>
              <span class="info-value"><?= e($bike->getColor()) ?></span>
            </div>
            <?php if ($bike->getYearOfManufacture()): ?>
              <div class="info-row">
                <span class="info-label">Rok vyroby</span>
                <span class="info-value"><?= e((string)$bike->getYearOfManufacture()) ?></span>
              </div>
            <?php endif; ?>
            <?php if ($bike->getFrameNumber() && $isOwner): ?>
              <div class="info-row">
                <span class="info-label">Cislo ramu</span>
                <span class="info-value"><?= e($bike->getFrameNumber()) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($bike->getDescription()): ?>
          <div class="mt-lg">
            <h3 class="section-title">Popis</h3>
            <p class="description-text"><?= nl2br(e($bike->getDescription())) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Owner actions -->
    <?php if ($isOwner): ?>
      <div class="card mt-lg">
        <div class="card-header">
          <h3><i data-lucide="settings" style="width:18px;height:18px;display:inline"></i> Sprava kola</h3>
        </div>
        <div class="card-body">
          <div class="btn-group">
            <a href="/bike/<?= $bike->getId() ?>/edit" class="btn btn-secondary">
              <i data-lucide="pencil"></i> Upravit
            </a>
            <?php if (!$bike->isStolen()): ?>
              <a href="/theft/report/<?= $bike->getId() ?>" class="btn btn-danger">
                <i data-lucide="shield-alert"></i> Nahlasit kradez
              </a>
            <?php else: ?>
              <?php if ($theftReport): ?>
                <form method="POST" action="/theft/<?= $theftReport->getId() ?>/resolve"
                      onsubmit="return confirm('Opravdu chcete zrusit hlaseni kradeze?')">
                  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                  <button type="submit" class="btn btn-success">
                    <i data-lucide="check-circle"></i> Kolo jsem ziskal zpet
                  </button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Found reports for owner -->
      <?php if (!empty($foundReports)): ?>
        <div class="mt-lg">
          <h3 class="section-title">
            Nahlasene nalezy
            <span class="badge-count"><?= count($foundReports) ?></span>
          </h3>

          <?php foreach ($foundReports as $fr): ?>
            <div class="card mb-md">
              <div class="card-body">
                <div class="flex items-center justify-between flex-wrap gap-sm mb-sm">
                  <span class="status-badge <?= e($fr->getStatusClass()) ?>">
                    <?= e($fr->getStatusLabel()) ?>
                  </span>
                  <span class="text-sm text-muted"><?= e($fr->getFormattedFoundDate()) ?></span>
                </div>

                <?php if ($fr->getFoundLocationText()): ?>
                  <p class="text-sm text-muted mb-sm">
                    <i data-lucide="map-pin" style="width:14px;height:14px;display:inline"></i>
                    <?= e($fr->getFoundLocationText()) ?>
                  </p>
                <?php endif; ?>

                <?php if ($fr->getDescription()): ?>
                  <p class="text-sm text-muted"><?= e(mb_strimwidth($fr->getDescription(), 0, 120, '...')) ?></p>
                <?php endif; ?>

                <a href="/found/<?= $fr->getId() ?>/conversation" class="btn btn-sm btn-primary mt-sm">
                  <i data-lucide="message-circle"></i> Otevrit konverzaci
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- QR Code -->
      <?php if (isset($qrDataUri)): ?>
        <div class="bike-qr mt-lg">
          <h3 class="section-title">QR kod kola</h3>
          <img src="<?= $qrDataUri ?>" alt="QR kod" class="qr-code-image">
          <div class="bike-qr-actions">
            <a href="/file/qr/<?= e($bike->getQrHash()) ?>"
               download="qr-kolo-<?= e($bike->getQrHash()) ?>.png"
               class="btn btn-secondary btn-sm">
              <i data-lucide="download"></i> Stahnout QR
            </a>
            <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
              <i data-lucide="printer"></i> Tisknout
            </button>
          </div>
          <p class="text-sm text-muted mt-md">Vytisknete a umistete na kolo. Po naskenovani zobrazi detail kola.</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</div>
