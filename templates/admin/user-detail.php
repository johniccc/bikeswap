<div class="page-header">
  <h1><?= e($user->getFullName()) ?></h1>
  <div class="page-header-actions">
    <a href="/admin/users" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na seznam
    </a>
  </div>
</div>

<!-- User info + edit form -->
<div class="card mb-lg">
  <div class="card-header flex items-center justify-between">
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
        <span class="info-value"><?= e($user->getFullName()) ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Email</span>
        <span class="info-value"><a href="mailto:<?= e($user->getEmail()) ?>"><?= e($user->getEmail()) ?></a></span>
      </div>
      <div class="info-row">
        <span class="info-label">Telefon</span>
        <span class="info-value"><?= $user->getPhone() ? '<a href="tel:' . e($user->getPhone()) . '">' . e($user->getPhone()) . '</a>' : '—' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Adresa</span>
        <span class="info-value"><?= $user->getAddress() ? e($user->getAddress()) : '—' ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Role</span>
        <span class="info-value">
          <?php $roleLabels = ['user' => 'Uživatel', 'police' => 'Policie', 'admin' => 'Admin']; ?>
          <?= e($roleLabels[$user->getRole()] ?? $user->getRole()) ?>
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
          <div class="address-autocomplete-wrapper">
            <input type="text" name="address" maxlength="255"
                   value="<?= e($user->getAddress() ?? '') ?>"
                   data-address-autocomplete
                   data-lat-input="admin_address_lat"
                   data-lng-input="admin_address_lng"
                   data-address-validated="admin_address_validated"
                   autocomplete="off">
          </div>
          <input type="hidden" id="admin_address_lat" name="address_lat">
          <input type="hidden" id="admin_address_lng" name="address_lng">
          <input type="hidden" id="admin_address_validated" name="address_validated"
                 value="<?= e($user->getAddress() ?? '') !== '' ? '1' : '0' ?>">
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
    <div class="card-body flex flex-col gap-xl">

      <!-- Ban / Unban -->
      <?php if (!$user->isAdmin()): ?>
        <div>
          <p class="section-label text-muted">
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
        <p class="section-label text-muted">
          Role
        </p>
        <form method="POST" action="/admin/users/<?= $user->getId() ?>/role">
          <input type="hidden" name="_csrf" value="<?= e($csrf ?? $session->csrfToken()) ?>">
          <div class="form-group mb-sm">
            <label for="role-select" class="text-sm text-muted">Vybrat roli</label>
            <select id="role-select" name="role" class="form-control">
              <option value="user" <?= $user->getRole() === 'user' ? 'selected' : '' ?>>Uživatel</option>
              <option value="police" <?= $user->getRole() === 'police' ? 'selected' : '' ?>>Policie</option>
              <option value="admin" <?= $user->getRole() === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">
            <i data-lucide="save"></i> Uložit roli
          </button>
        </form>
      </div>

      <!-- Danger zone -->
      <?php if (!$user->isAdmin()): ?>
        <hr class="divider" style="margin:0">
        <div>
          <p class="section-label text-danger">
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
  <a href="/admin/bikes/new?owner=<?= $user->getId() ?>" class="btn btn-primary btn-sm ml-auto">
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
      <a href="/admin/bikes/<?= $bike->getId() ?>" class="bike-card card-hover no-underline" style="color:inherit">
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

<!-- User devices -->
<div class="admin-detail-section">
  <div class="section-title-row">
    <h2 class="section-title">
      <i data-lucide="monitor-smartphone"></i>
      Zařízení uživatele
      <?php if (!empty($devices)): ?>
        <span class="badge-count"><?= count($devices) ?></span>
      <?php endif; ?>
    </h2>
  </div>

  <?php if (empty($devices)): ?>
    <div class="card">
      <div class="card-body">
        <p class="text-muted text-center">Žádná zaznamenaná zařízení. Zařízení se zobrazí poté, co se uživatel přihlásí z prohlížeče, ve kterém přijal cookies volbou „Přijmout vše".</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>IP adresa</th>
                <th>Platforma</th>
                <th>Rozlišení</th>
                <th>Timezone</th>
                <th>Jazyk</th>
                <th>První přístup</th>
                <th>Poslední přístup</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($devices as $device): ?>
                <tr>
                  <td><code><?= e($device->getIpAddress() ?? '—') ?></code></td>
                  <td><?= e($device->getPlatform() ?? '—') ?></td>
                  <td><?= e($device->getScreenResolution() ?? '—') ?></td>
                  <td><?= e($device->getTimezone() ?? '—') ?></td>
                  <td><?= e($device->getLanguage() ?? '—') ?></td>
                  <td class="nowrap"><?= date('d.m.Y H:i', strtotime($device->getFirstSeenAt())) ?></td>
                  <td class="nowrap"><?= date('d.m.Y H:i', strtotime($device->getLastSeenAt())) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Activity log -->
<div class="admin-detail-section">
  <div class="section-title-row">
    <h2 class="section-title">
      <i data-lucide="activity"></i>
      Historie aktivity
      <?php if (!empty($activityLogs)): ?>
        <span class="badge-count"><?= count($activityLogs) ?></span>
      <?php endif; ?>
    </h2>
  </div>

  <?php if (empty($activityLogs)): ?>
    <div class="card">
      <div class="card-body">
        <p class="text-muted text-center">Žádná zaznamenaná aktivita.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Datum</th>
                <th>Akce</th>
                <th>Entita</th>
                <th>IP adresa</th>
                <th>User-Agent</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($activityLogs as $log): ?>
                <?php
                  $actionClass = match(true) {
                    str_contains($log->getAction(), 'login') && !str_contains($log->getAction(), 'failed') => 'action-login',
                    str_contains($log->getAction(), 'failed') => 'action-failed',
                    $log->getAction() === 'logout' => 'action-logout',
                    $log->getAction() === 'register' => 'action-register',
                    str_starts_with($log->getAction(), 'admin_') => 'action-admin',
                    default => 'action-profile',
                  };
                ?>
                <tr>
                  <td class="nowrap"><?= date('d.m.Y H:i', strtotime($log->getCreatedAt())) ?></td>
                  <td>
                    <span class="activity-action-badge <?= $actionClass ?>">
                      <?= e($log->getActionLabel()) ?>
                    </span>
                  </td>
                  <td>
                    <?= e($log->getEntityType()) ?>
                    <?php if ($log->getEntityId()): ?>
                      <span class="text-muted">#<?= $log->getEntityId() ?></span>
                    <?php endif; ?>
                  </td>
                  <td><code><?= e($log->getIpAddress() ?? '—') ?></code></td>
                  <td class="ua-cell" title="<?= e($log->getUserAgent() ?? '') ?>">
                    <?= e($log->getShortUserAgent()) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

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
