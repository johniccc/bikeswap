<div class="page-header">
  <h1>Konverzace</h1>
</div>

<!-- Reservation conversations -->
<h2 class="section-title">
  Rezervace
  <span class="badge-count"><?= count($reservations) ?></span>
</h2>

<?php if (empty($reservations)): ?>
  <div class="card mb-xl">
    <div class="card-body">
      <p class="text-muted text-center">Žádné rezervace.</p>
    </div>
  </div>
<?php else: ?>
  <div class="card mb-xl">
    <!-- Desktop table -->
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Kolo</th>
            <th>Vlastník</th>
            <th>Výpůjčník</th>
            <th>Stav</th>
            <th>Datum</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $res): ?>
            <tr>
              <td>#<?= $res->getId() ?></td>
              <td><?= $res->getBike() ? e($res->getBike()->getFullName()) : '—' ?></td>
              <td><?= $res->getOwner() ? e($res->getOwner()->getFullName()) : '—' ?></td>
              <td><?= $res->getBorrower() ? e($res->getBorrower()->getFullName()) : '—' ?></td>
              <td><span class="status-badge status-<?= e($res->getStatus()) ?>"><?= e($res->getStatusLabel()) ?></span></td>
              <td><?= date('d.m.Y', strtotime($res->getCreatedAt())) ?></td>
              <td>
                <a href="/admin/conversation/reservation/<?= $res->getId() ?>" class="btn btn-sm btn-secondary">
                  <i data-lucide="message-square"></i> Vstoupit
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-card-list">
      <?php foreach ($reservations as $res): ?>
        <a href="/admin/conversation/reservation/<?= $res->getId() ?>" class="admin-card-item">
          <div class="admin-card-item-header">
            <span class="admin-card-item-title">
              <?= $res->getBike() ? e($res->getBike()->getFullName()) : 'Rezervace #' . $res->getId() ?>
            </span>
            <span class="status-badge status-<?= e($res->getStatus()) ?>"><?= e($res->getStatusLabel()) ?></span>
          </div>
          <div class="admin-card-item-meta">
            <?php if ($res->getOwner()): ?>
              <span>Vlastník: <?= e($res->getOwner()->getFullName()) ?></span>
            <?php endif; ?>
            <?php if ($res->getBorrower()): ?>
              <span>Výpůjčník: <?= e($res->getBorrower()->getFullName()) ?></span>
            <?php endif; ?>
            <span><?= date('d.m.Y', strtotime($res->getCreatedAt())) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Found report conversations -->
<h2 class="section-title">
  Hlášení nálezů
  <span class="badge-count"><?= count($foundReports) ?></span>
</h2>

<?php if (empty($foundReports)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Žádná hlášení nálezů.</p>
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
            <th>QR hash</th>
            <th>Místo nálezu</th>
            <th>Stav</th>
            <th>Datum</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($foundReports as $report): ?>
            <tr>
              <td>#<?= $report->getId() ?></td>
              <td><?= $report->getQrHashScanned() ? e(substr($report->getQrHashScanned(), 0, 8)) . '...' : '—' ?></td>
              <td><?= $report->getFoundLocationText() ? e(mb_substr($report->getFoundLocationText(), 0, 40)) : '—' ?></td>
              <td><span class="status-badge <?= e($report->getStatusClass()) ?>"><?= e($report->getStatusLabel()) ?></span></td>
              <td><?= date('d.m.Y', strtotime($report->getCreatedAt())) ?></td>
              <td>
                <a href="/admin/conversation/found/<?= $report->getId() ?>" class="btn btn-sm btn-secondary">
                  <i data-lucide="message-square"></i> Vstoupit
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-card-list">
      <?php foreach ($foundReports as $report): ?>
        <a href="/admin/conversation/found/<?= $report->getId() ?>" class="admin-card-item">
          <div class="admin-card-item-header">
            <span class="admin-card-item-title">Nález #<?= $report->getId() ?></span>
            <span class="status-badge <?= e($report->getStatusClass()) ?>"><?= e($report->getStatusLabel()) ?></span>
          </div>
          <div class="admin-card-item-meta">
            <?php if ($report->getFoundLocationText()): ?>
              <span><?= e(mb_substr($report->getFoundLocationText(), 0, 50)) ?></span>
            <?php endif; ?>
            <span><?= date('d.m.Y', strtotime($report->getCreatedAt())) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
