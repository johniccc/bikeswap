<div class="page-header">
  <h1>Kola</h1>
  <div class="page-header-actions">
    <a href="/admin/bikes/new" class="btn btn-primary btn-sm">
      <i data-lucide="plus"></i> Přidat kolo
    </a>
  </div>
</div>

<!-- Status filter -->
<div class="flex gap-sm mb-lg flex-wrap">
  <a href="/admin/bikes" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-secondary' ?>">Všechna</a>
  <a href="/admin/bikes?status=active" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' ?>">Aktivní</a>
  <a href="/admin/bikes?status=stolen" class="btn btn-sm <?= $statusFilter === 'stolen' ? 'btn-primary' : 'btn-secondary' ?>">Odcizená</a>
  <a href="/admin/bikes?status=inactive" class="btn btn-sm <?= $statusFilter === 'inactive' ? 'btn-primary' : 'btn-secondary' ?>">Neaktivní</a>
</div>

<?php if (empty($bikes)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Žádná kola nenalezena.</p>
    </div>
  </div>
<?php else: ?>
  <div class="bike-grid">
    <?php foreach ($bikes as $bike): ?>
      <a href="/admin/bikes/<?= $bike->getId() ?>" class="bike-card card-hover" style="text-decoration:none;color:inherit">
        <div class="bike-card-photo-wrap">
          <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
          <?php if ($primaryPhoto): ?>
            <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
          <?php else: ?>
            <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
          <?php endif; ?>
          <div class="bike-card-badges">
            <?php
              $statusLabels = ['active' => 'Aktivní', 'stolen' => 'Odcizené', 'shared' => 'Sdílené', 'inactive' => 'Neaktivní'];
            ?>
            <span class="status-badge status-<?= e($bike->getStatus()) ?>">
              <?= e($statusLabels[$bike->getStatus()] ?? $bike->getStatus()) ?>
            </span>
          </div>
        </div>
        <div class="bike-card-body">
          <div class="bike-card-name"><?= e($bike->getFullName()) ?></div>
          <div class="bike-card-meta"><i data-lucide="palette"></i> <?= e($bike->getColor()) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
