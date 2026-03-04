<div class="page-header">
  <h1>Moje kola</h1>
  <div class="page-header-actions">
    <a href="/bike/new" class="btn btn-primary">
      <i data-lucide="plus"></i> Registrovat kolo
    </a>
  </div>
</div>

<?php
  // Collect active cases for dashboard highlights
  $stolenBikes = array_filter($bikes, fn($b) => $b->isStolen());
  $bikesWithFinds = array_filter($bikes, fn($b) => ($foundReportCounts[$b->getId()] ?? 0) > 0);
?>
<?php if (!empty($stolenBikes) || !empty($bikesWithFinds)): ?>
  <div class="dashboard-alerts mb-lg">
    <?php foreach ($stolenBikes as $bike): ?>
      <a href="/bike/<?= e($bike->getQrHash()) ?>" class="dashboard-alert-card dashboard-alert-danger">
        <i data-lucide="shield-alert"></i>
        <div>
          <strong><?= e($bike->getFullName()) ?></strong>
          <span class="text-sm">Nahlášeno jako odcizené</span>
        </div>
        <i data-lucide="chevron-right" style="margin-left:auto;opacity:0.5"></i>
      </a>
    <?php endforeach; ?>
    <?php foreach ($bikesWithFinds as $bike): ?>
      <a href="/bike/<?= e($bike->getQrHash()) ?>" class="dashboard-alert-card dashboard-alert-success">
        <i data-lucide="map-pin"></i>
        <div>
          <strong><?= e($bike->getFullName()) ?></strong>
          <?php $fc = $foundReportCounts[$bike->getId()]; ?>
          <span class="text-sm"><?= $fc ?> nový nález<?= $fc > 4 ? 'ů' : ($fc > 1 ? 'y' : '') ?> — zkontrolujte konverzaci</span>
        </div>
        <i data-lucide="chevron-right" style="margin-left:auto;opacity:0.5"></i>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (empty($bikes)): ?>
  <div class="empty-state">
    <i data-lucide="bike"></i>
    <h3>Zatím nemáte žádné kolo</h3>
    <p>Zaregistrujte své první kolo a získejte unikátní QR kód pro jeho ochranu.</p>
    <a href="/bike/new" class="btn btn-primary">
      <i data-lucide="plus"></i> Zaregistrovat první kolo
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
                'active' => 'Aktivní',
                'stolen' => 'Odcizené',
                'shared' => 'Sdílené',
                'inactive' => 'Neaktivní',
              ];
            ?>
            <span class="status-badge status-<?= e($bike->getStatus()) ?>">
              <?= e($statusLabels[$bike->getStatus()] ?? $bike->getStatus()) ?>
            </span>

            <?php $frCount = $foundReportCounts[$bike->getId()] ?? 0; ?>
            <?php if ($frCount > 0): ?>
              <span class="found-report-badge">
                <i data-lucide="map-pin" style="width:12px;height:12px"></i>
                <?= $frCount ?> nález<?= $frCount > 4 ? 'ů' : ($frCount > 1 ? 'y' : '') ?>
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
