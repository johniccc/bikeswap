<div class="page-header">
  <h1>Moje kola</h1>
  <div class="page-header-actions">
    <a href="/bike/new" class="btn btn-primary">
      <i data-lucide="plus"></i> Registrovat kolo
    </a>
  </div>
</div>

<?php if (empty($bikes)): ?>
  <div class="empty-state">
    <i data-lucide="bike"></i>
    <h3>Zatim nemate zadne kolo</h3>
    <p>Zaregistrujte sve prvni kolo a ziskejte unikatni QR kod pro jeho ochranu.</p>
    <a href="/bike/new" class="btn btn-primary">
      <i data-lucide="plus"></i> Zaregistrovat prvni kolo
    </a>
  </div>
<?php else: ?>
  <div class="bike-grid">
    <?php foreach ($bikes as $bike): ?>
      <a href="/bike/<?= e($bike->getQrHash()) ?>" class="bike-card card-hover" style="text-decoration:none;color:inherit">
        <div class="bike-card-photo-wrap">
          <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
          <?php if ($primaryPhoto): ?>
            <img src="<?= e($primaryPhoto->getUrl()) ?>"
                 alt="<?= e($bike->getFullName()) ?>"
                 class="bike-card-photo">
          <?php else: ?>
            <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
          <?php endif; ?>

          <div class="bike-card-badges">
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

            <?php $frCount = $foundReportCounts[$bike->getId()] ?? 0; ?>
            <?php if ($frCount > 0): ?>
              <span class="found-report-badge">
                <i data-lucide="map-pin" style="width:12px;height:12px"></i>
                <?= $frCount ?> nalez<?= $frCount > 4 ? 'u' : ($frCount > 1 ? 'y' : '') ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="bike-card-body">
          <div class="bike-card-name"><?= e($bike->getFullName()) ?></div>
          <div class="bike-card-meta">
            <i data-lucide="palette"></i> <?= e($bike->getColor()) ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
