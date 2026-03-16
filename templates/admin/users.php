<div class="page-header">
  <h1>Uživatelé</h1>
</div>

<!-- Role filter -->
<div class="flex gap-sm mb-lg flex-wrap">
  <a href="/admin/users" class="btn btn-sm <?= !$roleFilter ? 'btn-primary' : 'btn-secondary' ?>">Všichni</a>
  <a href="/admin/users?role=user" class="btn btn-sm <?= $roleFilter === 'user' ? 'btn-primary' : 'btn-secondary' ?>">Uživatelé</a>
  <a href="/admin/users?role=police" class="btn btn-sm <?= $roleFilter === 'police' ? 'btn-primary' : 'btn-secondary' ?>">Policie</a>
  <a href="/admin/users?role=admin" class="btn btn-sm <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-secondary' ?>">Admini</a>
</div>

<?php
  $roleLabels = ['user' => 'Uživatel', 'police' => 'Policie', 'admin' => 'Admin'];
?>

<?php if (empty($users)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Žádní uživatelé nenalezeni.</p>
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
            <th>Jméno</th>
            <th>Email</th>
            <th>Role</th>
            <th>Karma</th>
            <th>Stav</th>
            <th>Registrace</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <?php
              $roleClass = match($user->getRole()) {
                  'admin' => 'status-active',
                  'police' => 'status-found',
                  default => 'status-unknown',
              };
            ?>
            <tr>
              <td class="text-muted">#<?= $user->getId() ?></td>
              <td><strong><?= e($user->getFullName()) ?></strong></td>
              <td class="text-muted"><?= e($user->getEmail()) ?></td>
              <td><span class="status-badge <?= $roleClass ?>"><?= e($roleLabels[$user->getRole()] ?? $user->getRole()) ?></span></td>
              <td><?= $user->getKarmaScore() ?></td>
              <td>
                <?php if ($user->isBanned()): ?>
                  <span class="status-badge status-stolen">Blokován</span>
                <?php else: ?>
                  <span class="status-badge status-active">Aktivní</span>
                <?php endif; ?>
              </td>
              <td class="text-muted text-sm"><?= date('d.m.Y', strtotime($user->getCreatedAt())) ?></td>
              <td>
                <a href="/admin/users/<?= $user->getId() ?>" class="btn btn-sm btn-secondary">Detail</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-card-list">
      <?php foreach ($users as $user): ?>
        <?php
          $roleClass = match($user->getRole()) {
              'admin' => 'status-active',
              'police' => 'status-found',
              default => 'status-unknown',
          };
        ?>
        <a href="/admin/users/<?= $user->getId() ?>" class="admin-card-item">
          <div class="admin-card-item-header">
            <span class="admin-card-item-title"><?= e($user->getFullName()) ?></span>
            <?php if ($user->isBanned()): ?>
              <span class="status-badge status-stolen">Blokován</span>
            <?php else: ?>
              <span class="status-badge <?= $roleClass ?>"><?= e($roleLabels[$user->getRole()] ?? $user->getRole()) ?></span>
            <?php endif; ?>
          </div>
          <div class="admin-card-item-meta">
            <span><?= e($user->getEmail()) ?></span>
            <span>Karma: <?= $user->getKarmaScore() ?></span>
            <span><?= date('d.m.Y', strtotime($user->getCreatedAt())) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
