<div data-auto-refresh="/dashboard/poll" data-refresh-interval="8000"></div>

<?php if (isset($currentUser) && !$currentUser->getPhone()): ?>
  <div class="alert alert-warning mb-lg" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
    <i data-lucide="phone-missed" style="flex-shrink:0"></i>
    <div style="flex:1">
      <strong>Nemáte vyplněné telefonní číslo.</strong>
      <p class="mt-xs text-sm">Telefon usnadní kontakt při nálezu kola nebo výpůjčce — ostatní uživatelé vás mohou rychleji zastihnout.</p>
    </div>
    <a href="/profile/settings" class="btn btn-sm btn-warning" style="flex-shrink:0">Doplnit kontakt</a>
  </div>
<?php endif; ?>

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
  $hasAlerts = !empty($stolenBikes) || !empty($bikesWithFinds) || !empty($myOpenFinds) || !empty($actionableReservations);
?>
<?php if ($hasAlerts): ?>
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
    <?php if (!empty($myOpenFinds)): ?>
      <?php foreach ($myOpenFinds as $find): ?>
        <?php $findBike = $findBikes[$find->getId()] ?? null; ?>
        <a href="/found/conversation/<?= e($find->getConversationToken()) ?>" class="dashboard-alert-card dashboard-alert-info">
          <i data-lucide="search"></i>
          <div>
            <strong>Váš nález: <?= $findBike ? e($findBike->getFullName()) : 'Kolo #' . $find->getBikeId() ?></strong>
            <span class="text-sm">
              <?= e($find->getStatusLabel()) ?>
              <?php if ($find->getFoundLocationText()): ?>
                — <?= e($find->getFoundLocationText()) ?>
              <?php endif; ?>
            </span>
          </div>
          <i data-lucide="chevron-right" style="margin-left:auto;opacity:0.5"></i>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($actionableReservations)): ?>
      <?php foreach ($actionableReservations as $res): ?>
        <?php
          $isOwner = $res->isOwnedBy($currentUser->getId());
          $resBike = $res->getBike();
          $otherUser = $isOwner ? $res->getBorrower() : $res->getOwner();
          $status = $res->getStatus();

          // Determine alert style and message
          if ($status === 'pending' && $isOwner) {
              $alertClass = 'dashboard-alert-warning';
              $icon = 'clock';
              $label = 'Čeká na vaše schválení';
          } elseif ($status === 'pending' && !$isOwner) {
              $alertClass = 'dashboard-alert-info';
              $icon = 'clock';
              $label = 'Čeká na schválení majitelem';
          } elseif ($status === 'approved') {
              $alertClass = 'dashboard-alert-success';
              $icon = 'calendar-check';
              $label = 'Schváleno — výpůjčka začíná ' . $res->getFormattedDateFrom();
          } elseif ($status === 'active') {
              $alertClass = 'dashboard-alert-info';
              $icon = 'bike';
              $label = 'Probíhající výpůjčka — vrácení do ' . $res->getFormattedDateTo();
          } elseif ($status === 'not_returned') {
              $alertClass = 'dashboard-alert-danger';
              $icon = 'alert-triangle';
              $label = 'Kolo nahlášeno jako nevrácené';
          } elseif ($status === 'disputed') {
              $alertClass = 'dashboard-alert-danger';
              $icon = 'flag';
              $label = 'Spor — čeká na rozhodnutí správce';
          } else {
              continue;
          }
        ?>
        <a href="/reservation/<?= $res->getId() ?>" class="dashboard-alert-card <?= $alertClass ?>">
          <i data-lucide="<?= $icon ?>"></i>
          <div>
            <strong><?= $resBike ? e($resBike->getFullName()) : 'Rezervace #' . $res->getId() ?></strong>
            <span class="text-sm">
              <?= $label ?>
              <?php if ($otherUser): ?>
                — <?= $isOwner ? 'vypůjčitel' : 'majitel' ?>: <?= e($otherUser->getFullName()) ?>
              <?php endif; ?>
            </span>
          </div>
          <i data-lucide="chevron-right" style="margin-left:auto;opacity:0.5"></i>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
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
                'seized' => 'Odvezeno',
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
            <?php $resStatus = $reservationStatuses[$bike->getId()] ?? null; ?>
            <?php if ($resStatus): ?>
              <?php
                $resLabels = ['pending' => 'Čeká na schválení', 'approved' => 'Rezervováno', 'active' => 'Zapůjčeno', 'not_returned' => 'Nevráceno', 'disputed' => 'Ve sporu'];
                $resClasses = ['pending' => 'status-pending', 'approved' => 'status-found', 'active' => 'status-active', 'not_returned' => 'status-stolen', 'disputed' => 'status-stolen'];
              ?>
              <span class="status-badge <?= $resClasses[$resStatus] ?? '' ?>">
                <i data-lucide="repeat" style="width:12px;height:12px"></i>
                <?= $resLabels[$resStatus] ?? $resStatus ?>
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
