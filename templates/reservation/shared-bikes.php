<div class="main-public-padded">
  <div class="page-header">
    <div>
      <h1>Sdilena kola k vypujcce</h1>
      <p class="text-muted">Prohlizejte kola nabizena k vypujcce od ostatnich uzivatelu.</p>
    </div>
  </div>

  <?php if (empty($bikes)): ?>
    <div class="empty-state">
      <i data-lucide="repeat"></i>
      <h3>Zadna kola momentalne nejsou k dispozici</h3>
      <p>Momentalne nikdo nenabizi sve kolo k vypujcce. Zkuste to pozdeji.</p>
    </div>
  <?php else: ?>
    <div class="bike-grid">
      <?php foreach ($bikes as $bike): ?>
        <div class="bike-card card-hover">
          <div class="bike-card-photo-wrap">
            <?php $photo = $bike->getPrimaryPhoto(); ?>
            <?php if ($photo): ?>
              <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
            <?php else: ?>
              <div class="bike-card-photo-placeholder"><i data-lucide="image"></i></div>
            <?php endif; ?>
            <div class="bike-card-badges">
              <?php $unavailable = in_array($bike->getId(), $unavailableIds ?? [], true); ?>
              <span class="availability-badge <?= $unavailable ? 'availability-unavailable' : 'availability-available' ?>">
                <?= $unavailable ? 'Nedostupne' : 'Dostupne' ?>
              </span>
            </div>
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
            <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-sm btn-ghost">
              <i data-lucide="eye"></i> Detail
            </a>
            <?php
              $isLoggedIn = isset($currentUser) && $currentUser !== null;
              $isOwner = $isLoggedIn && $bike->isOwnedBy($currentUser->getId());
            ?>
            <?php if (!$unavailable && $isLoggedIn && !$isOwner): ?>
              <a href="/reservation/new/<?= $bike->getId() ?>" class="btn btn-sm btn-primary">
                <i data-lucide="calendar-plus"></i> Rezervovat
              </a>
            <?php elseif (!$unavailable && !$isLoggedIn): ?>
              <a href="/login?redirect=/shared" class="btn btn-sm btn-primary">
                <i data-lucide="log-in"></i> Prihlasit a rezervovat
              </a>
            <?php elseif ($unavailable): ?>
              <span class="btn btn-sm btn-ghost" style="opacity:0.5;pointer-events:none">Nedostupne</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
