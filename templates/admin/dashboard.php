<div class="page-header">
  <h1>Administrace</h1>
</div>

<!-- Statistics cards -->
<div class="admin-stats-grid mb-lg">
  <?php if (!empty($isAdmin)): ?>
    <div class="card">
      <div class="card-body">
        <div class="admin-stat">
          <span class="admin-stat-value"><?= $stats['users'] ?></span>
          <span class="admin-stat-label">Uživatelů</span>
        </div>
        <a href="/admin/users" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <div class="admin-stat">
        <span class="admin-stat-value"><?= $stats['bikes'] ?></span>
        <span class="admin-stat-label">Kol celkem</span>
      </div>
      <a href="/admin/bikes" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="admin-stat">
        <span class="admin-stat-value"><?= $stats['bikes_stolen'] ?></span>
        <span class="admin-stat-label">Odcizených kol</span>
      </div>
      <a href="/admin/bikes?status=stolen" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="admin-stat">
        <span class="admin-stat-value"><?= $stats['thefts_open'] ?></span>
        <span class="admin-stat-label">Otevřených krádeží</span>
      </div>
      <a href="/admin/thefts?status=open" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
    </div>
  </div>

  <?php if (!empty($isAdmin)): ?>
    <div class="card">
      <div class="card-body">
        <div class="admin-stat">
          <span class="admin-stat-value"><?= $stats['reservations'] ?></span>
          <span class="admin-stat-label">Rezervací celkem</span>
        </div>
        <a href="/admin/reservations" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="admin-stat">
          <span class="admin-stat-value"><?= $stats['reservations_pending'] ?></span>
          <span class="admin-stat-label">Čekajících rezervací</span>
        </div>
        <a href="/admin/reservations?status=pending" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="admin-stat">
          <i data-lucide="message-square" style="width:28px;height:28px;color:var(--primary)"></i>
          <span class="admin-stat-label">Konverzace</span>
        </div>
        <a href="/admin/conversations" class="btn btn-sm btn-secondary" style="margin-top:auto">Zobrazit</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($isAdmin)): ?>
  <!-- Active disputes requiring admin action -->
  <h2 class="section-title">
    Spory k řešení
    <?php if (!empty($disputes)): ?>
      <span class="badge-count"><?= count($disputes) ?></span>
    <?php endif; ?>
  </h2>

  <?php if (empty($disputes)): ?>
    <div class="card">
      <div class="card-body">
        <p class="text-muted text-center">Žádné aktivní spory k řešení.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="dashboard-alerts">
      <?php foreach ($disputes as $res): ?>
        <?php
          $resBike = $res->getBike();
          $borrower = $res->getBorrower();
          $owner = $res->getOwner();
          $isDisputed = $res->isDisputed();
        ?>
        <a href="/reservation/<?= $res->getId() ?>" class="dashboard-alert-card <?= $isDisputed ? 'dashboard-alert-danger' : 'dashboard-alert-warning' ?>">
          <i data-lucide="<?= $isDisputed ? 'flag' : 'alert-triangle' ?>"></i>
          <div>
            <strong><?= $resBike ? e($resBike->getFullName()) : 'Rezervace #' . $res->getId() ?></strong>
            <span class="text-sm">
              <?= e($res->getStatusLabel()) ?>
              — majitel: <?= $owner ? e($owner->getName()) : '?' ?>
              — vypůjčitel: <?= $borrower ? e($borrower->getName()) : '?' ?>
              — <?= e($res->getDateRangeText()) ?>
            </span>
          </div>
          <i data-lucide="chevron-right" style="margin-left:auto;opacity:0.5"></i>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
