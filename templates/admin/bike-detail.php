<div class="page-header">
  <h1><?= e($bike->getFullName()) ?></h1>
  <div class="page-header-actions">
    <a href="/admin/bikes" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na kola
    </a>
    <?php if ($bike->isStolen()): ?>
      <a href="/found/report/<?= e($bike->getQrHash()) ?>" class="btn btn-success btn-sm">
        <i data-lucide="map-pin"></i> Nahlásit nález
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Bike info -->
<div class="card mb-lg">
  <div class="card-header">
    <h3>Informace o kole</h3>
  </div>
  <div class="card-body">
    <div class="info-row">
      <span class="info-label">ID</span>
      <span class="info-value">#<?= $bike->getId() ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Vlastník</span>
      <span class="info-value">
        <?php if ($owner): ?>
          <?php if ($currentUser->isAdmin()): ?>
            <a href="/admin/users/<?= $owner->getId() ?>" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:0.35rem;">
              <i data-lucide="user"></i> <?= e($owner->getFullName()) ?>
            </a>
          <?php else: ?>
            <?= e($owner->getFullName()) ?>
          <?php endif; ?>
        <?php else: ?>
          —
        <?php endif; ?>
      </span>
    </div>
    <?php if ($owner): ?>
      <?php if ($owner->getEmail()): ?>
        <div class="info-row">
          <span class="info-label">E-mail vlastníka</span>
          <span class="info-value"><a href="mailto:<?= e($owner->getEmail()) ?>"><?= e($owner->getEmail()) ?></a></span>
        </div>
      <?php endif; ?>
      <?php if ($owner->getPhone()): ?>
        <div class="info-row">
          <span class="info-label">Telefon vlastníka</span>
          <span class="info-value"><a href="tel:<?= e($owner->getPhone()) ?>"><?= e($owner->getPhone()) ?></a></span>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <div class="info-row">
      <span class="info-label">Stav</span>
      <span class="info-value">
        <?php $statusLabels = ['active' => 'Aktivní', 'stolen' => 'Odcizené', 'found' => 'Nalezeno', 'recovered' => 'Vráceno', 'seized' => 'Odvezeno']; ?>
        <span class="status-badge status-<?= e($bike->getStatus()) ?>">
          <?= e($statusLabels[$bike->getStatus()] ?? $bike->getStatus()) ?>
        </span>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Sdílení</span>
      <span class="info-value"><?= $bike->isShared() ? 'Ano' : 'Ne' ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Registrováno</span>
      <span class="info-value"><?= date('d.m.Y H:i', strtotime($bike->getCreatedAt())) ?></span>
    </div>
  </div>
</div>

<!-- Warning timeline -->
<?php if (!empty($warningTimeline) || $hasActiveWarning || $bike->isSeized()): ?>
<div class="card mb-lg">
  <div class="card-header">
    <h3><i data-lucide="history" class="icon-inline-lg"></i> Historie upozornění</h3>
  </div>
  <div class="card-body">
    <?php if (!empty($warningTimeline)): ?>
      <div class="warning-timeline">
        <?php foreach ($warningTimeline as $event): ?>
          <?php
            $color = $event->getTypeColor();
            $daysSince = (int) ((time() - strtotime($event->getCreatedAt())) / 86400);
          ?>
          <div class="timeline-item">
            <div class="timeline-icon timeline-icon-<?= $color ?>">
              <i data-lucide="<?= e($event->getTypeIcon()) ?>"></i>
            </div>
            <div class="timeline-content">
              <div class="timeline-content-header">
                <span class="timeline-type-label timeline-type-label-<?= $color ?>">
                  <?= e($event->getTypeLabel()) ?>
                </span>
                <span class="timeline-date">
                  <?= formatDateTime($event->getCreatedAt()) ?>
                  <?php if ($event->isActive() && $event->isWarning() && $daysSince > 0): ?>
                    <span class="timeline-days-ago <?= $daysSince > 14 ? 'timeline-days-ago-danger' : '' ?>">
                      <?= $daysSince ?> d
                    </span>
                  <?php endif; ?>
                </span>
              </div>
              <?php if ($event->getReason()): ?>
                <p class="timeline-detail"><?= e($event->getReason()) ?></p>
              <?php endif; ?>
              <?php if ($event->getLocationDescription()): ?>
                <p class="timeline-detail">
                  <span class="timeline-location">
                    <i data-lucide="map-pin"></i>
                    <?= e($event->getLocationDescription()) ?>
                  </span>
                </p>
              <?php endif; ?>
              <?php if ($event->getDeadline()): ?>
                <p class="timeline-detail">Doporučený termín: <?= e($event->getFormattedDeadline()) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($hasActiveWarning || $bike->isSeized()): ?>
      <div class="timeline-actions">
        <?php if ($hasActiveWarning): ?>
          <form method="POST" action="/admin/warnings/<?= $bike->getId() ?>/seize">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-danger"
                    data-confirm="Opravdu chcete toto kolo odvézt?">
              <i data-lucide="truck"></i> Odvézt kolo
            </button>
          </form>
        <?php endif; ?>
        <?php if ($bike->isSeized()): ?>
          <form method="POST" action="/admin/warnings/<?= $bike->getId() ?>/return">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-success"
                    data-confirm="Opravdu chcete kolo vrátit majiteli?" data-confirm-ok="Ano, vrátit" data-confirm-class="btn-success">
              <i data-lucide="undo-2"></i> Vrátit kolo majiteli
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($currentUser->isAdmin()): ?>
<!-- Edit form -->
<div class="card mb-lg">
  <div class="card-header">
    <h3>Upravit kolo</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="/admin/bikes/<?= $bike->getId() ?>/edit" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-row">
        <div class="form-group">
          <label for="brand">Značka *</label>
          <input type="text" id="brand" name="brand" required maxlength="100"
                 value="<?= e($bike->getBrand()) ?>">
        </div>
        <div class="form-group">
          <label for="model">Model</label>
          <input type="text" id="model" name="model" maxlength="100"
                 value="<?= e($bike->getModel() ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="color">Barva *</label>
          <input type="text" id="color" name="color" required maxlength="50"
                 value="<?= e($bike->getColor()) ?>">
        </div>
        <div class="form-group">
          <label for="year_of_manufacture">Rok výroby</label>
          <input type="number" id="year_of_manufacture" name="year_of_manufacture"
                 min="1950" max="<?= date('Y') ?>" value="<?= $bike->getYearOfManufacture() ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="frame_number">Sériové číslo</label>
        <input type="text" id="frame_number" name="frame_number" maxlength="100"
               value="<?= e($bike->getFrameNumber() ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="description">Popis</label>
        <textarea id="description" name="description" rows="3"><?= e($bike->getDescription() ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="status">Stav</label>
        <select id="status" name="status" class="form-control">
          <option value="active" <?= $bike->getStatus() === 'active' ? 'selected' : '' ?>>Aktivní</option>
          <option value="stolen" <?= $bike->getStatus() === 'stolen' ? 'selected' : '' ?>>Odcizené</option>
          <option value="found" <?= $bike->getStatus() === 'found' ? 'selected' : '' ?>>Nalezeno</option>
          <option value="recovered" <?= $bike->getStatus() === 'recovered' ? 'selected' : '' ?>>Vráceno</option>
          <option value="seized" <?= $bike->getStatus() === 'seized' ? 'selected' : '' ?>>Odvezeno</option>
        </select>
      </div>

      <div class="form-check mb-md">
        <input type="checkbox" name="is_shared" value="1" id="is_shared"
               <?= $bike->isShared() ? 'checked' : '' ?>>
        <label for="is_shared">Nabídnout k výpůjčce</label>
      </div>

      <fieldset>
        <legend>Přidat nové fotografie</legend>
        <div class="form-group">
          <label class="form-file-label" for="photo-input">
            <i data-lucide="image-plus"></i> Vybrat fotografie
          </label>
          <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" style="display:none">
          <div class="photo-preview-grid" id="photo-preview"></div>
          <input type="hidden" name="primary_index" id="primary-index" value="0">
        </div>
      </fieldset>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i> Uložit změny
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Existing photos -->
<?php if (!empty($bike->getPhotos())): ?>
<div class="card mb-lg">
  <div class="card-header">
    <h3>Fotografie</h3>
  </div>
  <div class="card-body">
    <div class="existing-photos-grid">
      <?php foreach ($bike->getPhotos() as $photo): ?>
        <div class="existing-photo-card <?= $photo->isPrimary() ? 'primary-photo' : '' ?>">
          <img src="<?= e($photo->getUrl()) ?>" alt="Foto kola">
          <?php if ($currentUser->isAdmin()): ?>
          <div class="existing-photo-actions">
            <?php if ($photo->isPrimary()): ?>
              <span class="photo-preview-badge" title="Hlavní fotografie"><i data-lucide="star"></i></span>
            <?php else: ?>
              <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/primary" style="margin:0">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="photo-action-btn" title="Nastavit jako hlavní">
                  <i data-lucide="star"></i>
                </button>
              </form>
            <?php endif; ?>
            <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/delete" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="photo-action-btn photo-action-danger" title="Smazat fotku"
                      data-confirm="Opravdu smazat tuto fotku?">
                <i data-lucide="trash-2"></i>
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Danger zone -->
<div class="danger-zone">
  <h3><i data-lucide="alert-triangle" style="width:18px;height:18px;display:inline"></i> Nebezpečná zóna</h3>
  <p>Smazáním kola se trvale odstraní všechny údaje, fotografie, rezervace a hlášení spojené s tímto kolem.</p>
  <form method="POST" action="/admin/bikes/<?= $bike->getId() ?>/delete">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="btn btn-danger"
            data-confirm="Opravdu chcete trvale smazat toto kolo? Tuto akci nelze vrátit.">
      <i data-lucide="trash-2"></i> Smazat kolo
    </button>
  </form>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('[data-confirm]').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
  });
});
</script>
