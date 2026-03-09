<div class="page-header">
  <h1><?= e($user->getName()) ?></h1>
  <div class="page-header-actions">
    <a href="/admin/users" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na seznam
    </a>
  </div>
</div>

<!-- User info card -->
<div class="card mb-lg">
  <div class="card-header">
    <h3>Informace o uživateli</h3>
  </div>
  <div class="card-body">
    <div class="info-row">
      <span class="info-label">ID</span>
      <span class="info-value">#<?= $user->getId() ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Jméno</span>
      <span class="info-value"><?= e($user->getName()) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Email</span>
      <span class="info-value"><?= e($user->getEmail()) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Telefon</span>
      <span class="info-value"><?= $user->getPhone() ? e($user->getPhone()) : '—' ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Adresa</span>
      <span class="info-value"><?= $user->getAddress() ? e($user->getAddress()) : '—' ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Role</span>
      <span class="info-value">
        <?php
          $roleLabels = ['user' => 'Uživatel', 'police' => 'Policie', 'admin' => 'Admin'];
        ?>
        <?= $roleLabels[$user->getRole()] ?? $user->getRole() ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Karma</span>
      <span class="info-value"><?= $user->getKarmaScore() ?> (<?= e($user->getKarmaLevel()) ?>)</span>
    </div>
    <div class="info-row">
      <span class="info-label">Stav</span>
      <span class="info-value">
        <?php if ($user->isBanned()): ?>
          <span class="status-badge status-stolen">Blokován</span>
        <?php else: ?>
          <span class="status-badge status-active">Aktivní</span>
        <?php endif; ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Registrace</span>
      <span class="info-value"><?= date('d.m.Y H:i', strtotime($user->getCreatedAt())) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Poslední přihlášení</span>
      <span class="info-value"><?= $user->getLastLoginAt() ? date('d.m.Y H:i', strtotime($user->getLastLoginAt())) : '—' ?></span>
    </div>
  </div>
</div>

<!-- Admin actions -->
<?php if ($user->getId() !== $currentUser->getId()): ?>
  <div class="card mb-lg">
    <div class="card-header">
      <h3>Akce</h3>
    </div>
    <div class="card-body">
      <div class="flex gap-sm flex-wrap">

        <!-- Ban / Unban -->
        <?php if (!$user->isAdmin()): ?>
          <?php if ($user->isBanned()): ?>
            <form method="POST" action="/admin/users/<?= $user->getId() ?>/unban">
              <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
              <button type="submit" class="btn btn-success btn-sm">
                <i data-lucide="shield-check"></i> Odblokovat
              </button>
            </form>
          <?php else: ?>
            <form method="POST" action="/admin/users/<?= $user->getId() ?>/ban">
              <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
              <button type="submit" class="btn btn-danger btn-sm"
                      data-confirm="Opravdu chcete tohoto uživatele zablokovat?">
                <i data-lucide="shield-off"></i> Zablokovat
              </button>
            </form>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Role change -->
        <form method="POST" action="/admin/users/<?= $user->getId() ?>/role" class="flex gap-sm" style="align-items:flex-end">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
          <select name="role" class="form-control" style="width:auto">
            <option value="user" <?= $user->getRole() === 'user' ? 'selected' : '' ?>>Uživatel</option>
            <option value="police" <?= $user->getRole() === 'police' ? 'selected' : '' ?>>Policie</option>
            <option value="admin" <?= $user->getRole() === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
          <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="refresh-cw"></i> Změnit roli
          </button>
        </form>

      </div>
    </div>
  </div>
<?php endif; ?>

<!-- User's bikes -->
<h2 class="section-title">
  Kola uživatele
  <span class="badge-count"><?= count($bikes) ?></span>
</h2>

<?php if (empty($bikes)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Tento uživatel nemá žádná kola.</p>
    </div>
  </div>
<?php else: ?>
  <div class="bike-grid">
    <?php foreach ($bikes as $bike): ?>
      <a href="/bike/<?= e($bike->getQrHash()) ?>" class="bike-card card-hover" style="text-decoration:none;color:inherit">
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
