<div class="page-header">
  <h1>Profil</h1>
  <div class="page-header-actions">
    <a href="/profile/settings" class="btn btn-secondary btn-sm">
      <i data-lucide="settings"></i> Nastavení
    </a>
  </div>
</div>

<!-- Karma card -->
<div class="card mb-lg">
  <div class="card-body">
    <div class="flex gap-lg items-center flex-wrap">
      <div class="profile-avatar">
        <i data-lucide="user" style="width:32px;height:32px"></i>
      </div>
      <div>
        <h2 style="margin-bottom:0.15rem"><?= e($user->getName()) ?></h2>
        <p class="text-muted text-sm"><?= e($user->getEmail()) ?></p>
      </div>
      <div style="margin-left:auto;text-align:right">
        <div class="karma-score"><?= e((string)$user->getKarmaScore()) ?></div>
        <span class="status-badge status-active"><?= e($user->getKarmaLevel()) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Bikes -->
<div class="card mb-lg">
  <div class="card-header">
    <h3><i data-lucide="bike" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Moje kola</h3>
  </div>
  <div class="card-body">
    <?php if (empty($bikes)): ?>
      <p class="text-muted">Nemáte žádná registrovaná kola.</p>
      <a href="/bike/new" class="btn btn-primary btn-sm mt-sm">
        <i data-lucide="plus"></i> Přidat kolo
      </a>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Kolo</th>
              <th>Stav</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bikes as $bike): ?>
              <tr>
                <td>
                  <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
                </td>
                <td>
                  <span class="status-badge status-<?= e($bike->getStatus()) ?>">
                    <?= e($bike->getStatus()) ?>
                  </span>
                </td>
                <td>
                  <a href="/bike/<?= e((string)$bike->getId()) ?>/edit" class="btn btn-sm btn-ghost">
                    <i data-lucide="edit"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Reservations -->
<div class="card mb-lg">
  <div class="card-header">
    <h3><i data-lucide="calendar" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Moje rezervace</h3>
  </div>
  <div class="card-body">
    <?php if (empty($reservations)): ?>
      <p class="text-muted">Nemáte žádné rezervace jako vypůjčitel.</p>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Rezervace</th>
              <th>Stav</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $r): ?>
              <tr>
                <td>Rezervace #<?= e((string)$r->getId()) ?></td>
                <td>
                  <span class="status-badge <?= e($r->getStatusClass()) ?>">
                    <?= e($r->getStatusLabel()) ?>
                  </span>
                </td>
                <td>
                  <a href="/reservation/<?= e((string)$r->getId()) ?>" class="btn btn-sm btn-ghost">Detail</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Found reports -->
<?php if (!empty($foundReports)): ?>
<div class="card">
  <div class="card-header">
    <h3><i data-lucide="map-pin" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Moje nálezy</h3>
  </div>
  <div class="card-body">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nález</th>
            <th>Datum</th>
            <th>Stav</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($foundReports as $report): ?>
            <tr>
              <td>Nález #<?= e((string)$report->getId()) ?></td>
              <td class="text-sm"><?= e($report->getFormattedFoundDate()) ?></td>
              <td>
                <span class="status-badge <?= e($report->getStatusClass()) ?>">
                  <?= e($report->getStatusLabel()) ?>
                </span>
              </td>
              <td>
                <a href="/found/conversation/<?= e($report->getConversationToken()) ?>" class="btn btn-sm btn-ghost">
                  <i data-lucide="message-circle"></i> Konverzace
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
