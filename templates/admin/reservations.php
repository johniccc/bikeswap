<div class="page-header">
  <h1>Rezervace</h1>
</div>

<!-- Status filter -->
<div class="flex gap-sm mb-lg flex-wrap">
  <a href="/admin/reservations" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">Všechny</a>
  <a href="/admin/reservations?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Čekající</a>
  <a href="/admin/reservations?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-secondary' ?>">Schválené</a>
  <a href="/admin/reservations?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?>">Aktivní</a>
  <a href="/admin/reservations?status=not_returned" class="btn btn-sm <?= $statusFilter === 'not_returned' ? 'btn-primary' : 'btn-secondary' ?>">Nevrácené</a>
  <a href="/admin/reservations?status=disputed" class="btn btn-sm <?= $statusFilter === 'disputed' ? 'btn-primary' : 'btn-secondary' ?>">Sporné</a>
  <a href="/admin/reservations?status=completed" class="btn btn-sm <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary' ?>">Dokončené</a>
</div>

<?php if (empty($reservations)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Žádné rezervace nenalezeny.</p>
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
            <th>Majitel</th>
            <th>Vypůjčitel</th>
            <th>Termín</th>
            <th>Stav</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $res): ?>
            <?php
              $resBike = $res->getBike();
              $owner = $res->getOwner();
              $borrower = $res->getBorrower();
            ?>
            <tr>
              <td class="text-muted">#<?= $res->getId() ?></td>
              <td>
                <?php if ($resBike): ?>
                  <a href="/admin/bikes/<?= $resBike->getId() ?>"><?= e($resBike->getFullName()) ?></a>
                <?php else: ?>
                  Kolo #<?= $res->getBikeId() ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($owner): ?>
                  <a href="/admin/users/<?= $owner->getId() ?>"><?= e($owner->getFullName()) ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td>
                <?php if ($borrower): ?>
                  <a href="/admin/users/<?= $borrower->getId() ?>"><?= e($borrower->getFullName()) ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="text-sm"><?= e($res->getDateRangeText()) ?></td>
              <td>
                <span class="status-badge <?= e($res->getStatusClass()) ?>"><?= e($res->getStatusLabel()) ?></span>
              </td>
              <td>
                <a href="/reservation/<?= $res->getId() ?>" class="btn btn-sm btn-secondary">Detail</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-card-list">
      <?php foreach ($reservations as $res): ?>
        <?php
          $resBike = $res->getBike();
          $owner = $res->getOwner();
          $borrower = $res->getBorrower();
        ?>
        <a href="/reservation/<?= $res->getId() ?>" class="admin-card-item">
          <div class="admin-card-item-header">
            <span class="admin-card-item-title"><?= $resBike ? e($resBike->getFullName()) : 'Rezervace #' . $res->getId() ?></span>
            <span class="status-badge <?= e($res->getStatusClass()) ?>"><?= e($res->getStatusLabel()) ?></span>
          </div>
          <div class="admin-card-item-meta">
            <span><?= e($res->getDateRangeText()) ?></span>
            <?php if ($owner): ?><span>Majitel: <?= e($owner->getFullName()) ?></span><?php endif; ?>
            <?php if ($borrower): ?><span>Vypůjčitel: <?= e($borrower->getFullName()) ?></span><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
