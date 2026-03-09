<div class="main-public-padded">
  <div class="page-header">
    <div>
      <h1>Sdílená kola k výpůjčce</h1>
      <p class="text-muted">Prohlížejte kola nabízená k výpůjčce od ostatních uživatelů.</p>
    </div>
  </div>

  <?php if (empty($bikes)): ?>
    <div class="empty-state">
      <i data-lucide="repeat"></i>
      <h3>Žádná kola momentálně nejsou k dispozici</h3>
      <p>Momentálně nikdo nenabízí své kolo k výpůjčce. Zkuste to později.</p>
    </div>
  <?php else: ?>
    <div class="bike-grid">
      <?php foreach ($bikes as $bike): ?>
        <?php
          $isLoggedIn = isset($currentUser) && $currentUser !== null;
          $isOwner = $isLoggedIn && $bike->isOwnedBy($currentUser->getId());
        ?>
        <div class="bike-card card-hover" data-href="/bike/<?= e($bike->getQrHash()) ?>" style="cursor:pointer">
          <div class="bike-card-photo-wrap">
            <?php $photo = $bike->getPrimaryPhoto(); ?>
            <?php if ($photo): ?>
              <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
            <?php else: ?>
              <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
            <?php endif; ?>
          </div>

          <div class="bike-card-body">
            <div class="bike-card-name"><?= e($bike->getFullName()) ?></div>
            <div class="bike-card-meta">
              <i data-lucide="palette"></i> <?= e($bike->getColor()) ?>
              <?php if ($bike->getYearOfManufacture()): ?>
                &middot; <?= $bike->getYearOfManufacture() ?>
              <?php endif; ?>
            </div>

            <?php if ($bike->getDescription()): ?>
              <p class="text-muted text-sm mt-sm"><?= e(mb_substr($bike->getDescription(), 0, 100)) ?><?= mb_strlen($bike->getDescription()) > 100 ? '...' : '' ?></p>
            <?php endif; ?>
          </div>

          <div class="bike-card-footer">
            <?php if ($isLoggedIn && !$isOwner): ?>
              <a href="/reservation/new/<?= $bike->getId() ?>" class="btn btn-sm btn-primary">
                <i data-lucide="calendar-plus"></i> Rezervovat
              </a>
            <?php elseif (!$isLoggedIn): ?>
              <a href="/login?redirect=<?= urlencode('/bike/' . $bike->getQrHash()) ?>" class="btn btn-sm btn-primary">
                <i data-lucide="log-in"></i> Přihlásit a rezervovat
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
