<div class="page-header">
  <h1>Hlášení krádeží</h1>
</div>

<!-- Status filter -->
<div class="flex gap-sm mb-lg flex-wrap">
  <a href="/admin/thefts" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">Všechny</a>
  <a href="/admin/thefts?status=open" class="btn btn-sm <?= $statusFilter === 'open' ? 'btn-primary' : 'btn-secondary' ?>">Otevřené</a>
  <a href="/admin/thefts?status=investigating" class="btn btn-sm <?= $statusFilter === 'investigating' ? 'btn-primary' : 'btn-secondary' ?>">Vyšetřované</a>
  <a href="/admin/thefts?status=resolved" class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-secondary' ?>">Vyřešené</a>
</div>

<?php
  $statusLabels = ['open' => 'Otevřené', 'investigating' => 'Vyšetřované', 'resolved' => 'Vyřešené'];
?>

<?php if (empty($reports)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Žádná hlášení nenalezena.</p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <!-- Desktop table -->
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Kolo</th>
            <th>Datum krádeže</th>
            <th>Místo</th>
            <th>Stav</th>
            <th>Nahlášeno</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $report): ?>
            <?php
              $reportBike = $report->getBike();
              $statusClass = match($report->getStatus()) {
                  'open' => 'status-stolen',
                  'investigating' => 'status-found',
                  'resolved' => 'status-active',
                  default => 'status-unknown',
              };
            ?>
            <tr>
              <td class="text-muted">#<?= $report->getId() ?></td>
              <td>
                <?php if ($reportBike): ?>
                  <a href="/admin/bikes/<?= $reportBike->getId() ?>"><?= e($reportBike->getFullName()) ?></a>
                <?php else: ?>
                  Kolo #<?= $report->getBikeId() ?>
                <?php endif; ?>
              </td>
              <td class="text-sm"><?= $report->getTheftDate() ? e($report->getFormattedTheftDate()) : '—' ?></td>
              <td class="text-sm"><?= $report->getTheftLocationText() ? e($report->getTheftLocationText()) : '—' ?></td>
              <td><span class="status-badge <?= $statusClass ?>"><?= e($statusLabels[$report->getStatus()] ?? $report->getStatus()) ?></span></td>
              <td class="text-muted text-sm"><?= date('d.m.Y', strtotime($report->getCreatedAt())) ?></td>
              <td>
                <?php if ($reportBike): ?>
                  <a href="/admin/bikes/<?= $reportBike->getId() ?>" class="btn btn-sm btn-secondary">Detail kola</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-card-list">
      <?php foreach ($reports as $report): ?>
        <?php
          $reportBike = $report->getBike();
          $statusClass = match($report->getStatus()) {
              'open' => 'status-stolen',
              'investigating' => 'status-found',
              'resolved' => 'status-active',
              default => 'status-unknown',
          };
        ?>
        <a href="<?= $reportBike ? '/admin/bikes/' . $reportBike->getId() : '#' ?>" class="admin-card-item">
          <div class="admin-card-item-header">
            <span class="admin-card-item-title"><?= $reportBike ? e($reportBike->getFullName()) : 'Kolo #' . $report->getBikeId() ?></span>
            <span class="status-badge <?= $statusClass ?>"><?= e($statusLabels[$report->getStatus()] ?? $report->getStatus()) ?></span>
          </div>
          <div class="admin-card-item-meta">
            <?php if ($report->getTheftDate()): ?><span><?= e($report->getFormattedTheftDate()) ?></span><?php endif; ?>
            <?php if ($report->getTheftLocationText()): ?><span><?= e($report->getTheftLocationText()) ?></span><?php endif; ?>
            <span>Nahlášeno: <?= date('d.m.Y', strtotime($report->getCreatedAt())) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
