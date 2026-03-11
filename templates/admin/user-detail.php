<div class="page-header">
  <h1><?= e($user->getName()) ?></h1>
  <div class="page-header-actions">
    <a href="/admin/users" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na seznam
    </a>
  </div>
</div>

<!-- User info + edit form -->
<div class="card mb-lg">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
    <h3>Informace o uživateli</h3>
    <button type="button" class="btn btn-secondary btn-sm" id="toggle-edit-user">
      <i data-lucide="pencil"></i> Upravit
    </button>
  </div>
  <div class="card-body">

    <!-- Read-only view -->
    <div id="user-info-view">
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
          <?php $roleLabels = ['user' => 'Uživatel', 'police' => 'Policie', 'admin' => 'Admin']; ?>
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

    <!-- Edit form (hidden by default) -->
    <div id="user-edit-form" style="display:none">
      <form method="POST" action="/admin/users/<?= $user->getId() ?>/edit">
        <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">

        <div class="form-group">
          <label>Jméno</label>
          <input type="text" name="first_name" required maxlength="50" value="<?= e($user->getFirstName()) ?>">
        </div>
        <div class="form-group">
          <label>Příjmení</label>
          <input type="text" name="surname" required maxlength="50" value="<?= e($user->getSurname()) ?>">
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input type="email" name="email" required maxlength="255" value="<?= e($user->getEmail()) ?>">
        </div>
        <div class="form-group">
          <label>Telefon</label>
          <input type="tel" name="phone" maxlength="20"
                 value="<?= e($user->getPhone() ?? '') ?>" placeholder="+420 123 456 789">
        </div>
        <div class="form-group">
          <label>Adresa</label>
          <input type="text" name="address" maxlength="255" value="<?= e($user->getAddress() ?? '') ?>">
        </div>

        <div class="flex gap-sm">
          <button type="submit" class="btn btn-primary btn-sm">
            <i data-lucide="save"></i> Uložit
          </button>
          <button type="button" class="btn btn-ghost btn-sm" id="cancel-edit-user">Zrušit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Admin actions -->
<?php if ($user->getId() !== $currentUser->getId()): ?>
  <div class="card mb-lg">
    <div class="card-header">
      <h3>Akce</h3>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:1.25rem">

      <!-- Ban / Unban -->
      <?php if (!$user->isAdmin()): ?>
        <div>
          <p style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);margin-bottom:0.5rem">
            Stav účtu
          </p>
          <?php if ($user->isBanned()): ?>
            <form method="POST" action="/admin/users/<?= $user->getId() ?>/unban">
              <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
              <button type="submit" class="btn btn-success btn-sm">
                <i data-lucide="shield-check"></i> Odblokovat uživatele
              </button>
            </form>
          <?php else: ?>
            <form method="POST" action="/admin/users/<?= $user->getId() ?>/ban">
              <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
              <button type="submit" class="btn btn-danger btn-sm"
                      data-confirm="Opravdu chcete tohoto uživatele zablokovat?">
                <i data-lucide="shield-off"></i> Zablokovat uživatele
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Role change -->
      <div>
        <p style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);margin-bottom:0.5rem">
          Role
        </p>
        <form method="POST" action="/admin/users/<?= $user->getId() ?>/role">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
          <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
            <select name="role" class="form-control" style="width:auto">
              <option value="user" <?= $user->getRole() === 'user' ? 'selected' : '' ?>>Uživatel</option>
              <option value="police" <?= $user->getRole() === 'police' ? 'selected' : '' ?>>Policie</option>
              <option value="admin" <?= $user->getRole() === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">
              <i data-lucide="save"></i> Uložit roli
            </button>
          </div>
        </form>
      </div>

      <!-- Danger zone -->
      <?php if (!$user->isAdmin()): ?>
        <hr style="border:none;border-top:1px solid var(--border);margin:0">
        <div>
          <p style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--danger);margin-bottom:0.5rem">
            Nebezpečná zóna
          </p>
          <form method="POST" action="/admin/users/<?= $user->getId() ?>/delete">
            <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
            <button type="submit" class="btn btn-danger btn-sm"
                    data-confirm="Opravdu chcete tohoto uživatele TRVALE smazat? Tato akce je nevratná.">
              <i data-lucide="trash-2"></i> Smazat uživatele
            </button>
          </form>
        </div>
      <?php endif; ?>

    </div>
  </div>
<?php endif; ?>

<!-- User's bikes -->
<div class="section-title-row">
  <h2 class="section-title">
    Kola uživatele
    <span class="badge-count"><?= count($bikes) ?></span>
  </h2>
  <a href="/admin/bikes/new?owner=<?= $user->getId() ?>" class="btn btn-primary btn-sm" style="margin-left:auto">
    <i data-lucide="plus"></i> Přidat kolo
  </a>
</div>

<?php if (empty($bikes)): ?>
  <div class="card">
    <div class="card-body">
      <p class="text-muted text-center">Tento uživatel nemá žádná kola.</p>
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

<script>
(function () {
  var toggleBtn = document.getElementById('toggle-edit-user');
  var cancelBtn = document.getElementById('cancel-edit-user');
  var infoView  = document.getElementById('user-info-view');
  var editForm  = document.getElementById('user-edit-form');

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      infoView.style.display = 'none';
      editForm.style.display = 'block';
      toggleBtn.style.display = 'none';
    });
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      infoView.style.display = 'block';
      editForm.style.display = 'none';
      toggleBtn.style.display = '';
    });
  }

  // Confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm(btn.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });
}());
</script>
