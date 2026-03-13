<div class="page-header">
  <div class="flex justify-between align-center flex-wrap gap-sm">
    <h1>Upozornění na kola</h1>
    <a href="/admin/warnings/new" class="btn btn-primary">
      <i data-lucide="plus"></i> Nové upozornění
    </a>
  </div>
</div>

<!-- Status filter -->
<div class="flex gap-sm mb-lg flex-wrap">
  <a href="/admin/warnings" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">Vše (<?= $counts['all'] ?>)</a>
  <a href="/admin/warnings?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?>">Aktivní (<?= $counts['active'] ?>)</a>
  <a href="/admin/warnings?status=resolved" class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-secondary' ?>">Vyřešené (<?= $counts['resolved'] ?>)</a>
  <a href="/admin/warnings?status=expired" class="btn btn-sm <?= $statusFilter === 'expired' ? 'btn-primary' : 'btn-secondary' ?>">Vypršelé (<?= $counts['expired'] ?>)</a>
</div>

<?php if (empty($warnings)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Žádná upozornění nenalezena.</p>
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
            <th>Termín</th>
            <th>Stav</th>
            <th>Akce</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($warnings as $w): ?>
            <?php
              $bike = $bikesMap[$w->getBikeId()] ?? null;
              $owner = $bike ? ($ownersMap[$bike->getOwnerId()] ?? null) : null;
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
                  <a href="/admin/users/<?= $owner->getId() ?>"><?= e($owner->getName()) ?></a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td><?= e($w->getLocationDescription()) ?></td>
              <td><?= e($w->getFormattedDeadline()) ?></td>
              <td>
                <span class="status-badge <?= e($w->getStatusClass()) ?>"><?= e($w->getStatusLabel()) ?></span>
              </td>
              <td>
                <?php if ($w->isActive()): ?>
                  <form method="post" action="/admin/warnings/<?= $w->getId() ?>/resolve" style="display:inline">
                    <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Opravdu označit jako vyřešené?')">Vyřešit</button>
                  </form>
                <?php endif; ?>
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
            <span class="status-badge <?= e($w->getStatusClass()) ?>"><?= e($w->getStatusLabel()) ?></span>
          </div>
          <div class="admin-card-item-meta">
            <span><?= e($w->getLocationDescription()) ?></span>
            <span>Termín: <?= e($w->getFormattedDeadline()) ?></span>
            <?php if ($owner): ?><span>Vlastník: <?= e($owner->getName()) ?></span><?php endif; ?>
          </div>
          <?php if ($w->isActive()): ?>
            <div style="margin-top: 0.5rem">
              <form method="post" action="/admin/warnings/<?= $w->getId() ?>/resolve">
                <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">
                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Opravdu označit jako vyřešené?')">Vyřešit</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
