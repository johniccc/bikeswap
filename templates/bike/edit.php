<div class="page-header">
  <h1>Upravit kolo</h1>
  <div class="page-header-actions">
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">
      <i data-lucide="arrow-left"></i> Zpět na detail
    </a>
  </div>
</div>

<form method="POST" action="/bike/<?= $bike->getId() ?>/edit" enctype="multipart/form-data" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Základní údaje</legend>

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
               min="1950" max="<?= date('Y') ?>"
               value="<?= $bike->getYearOfManufacture() ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="frame_number">Sériové číslo</label>
      <input type="text" id="frame_number" name="frame_number" maxlength="100"
             value="<?= e($bike->getFrameNumber() ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="description">Popis</label>
      <textarea id="description" name="description" rows="4"><?= e($bike->getDescription() ?? '') ?></textarea>
    </div>
  </fieldset>

  <fieldset>
    <legend>Sdílení</legend>
    <div class="form-check">
      <input type="checkbox" name="is_shared" value="1" id="is_shared"
             <?= $bike->isShared() ? 'checked' : '' ?>>
      <label for="is_shared">Nabídnout kolo k výpůjčce ostatním uživatelům</label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Přidat nové fotografie</legend>
    <div class="form-group">
      <label class="form-file-label" for="photo-input">
        <i data-lucide="image-plus"></i> Vybrat fotografie
      </label>
      <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" style="display:none">
      <div class="photo-preview-grid" id="photo-preview"></div>
      <input type="hidden" name="primary_index" id="primary-index" value="0">
      <p class="form-text">Podporované formáty: JPG, PNG, WebP.</p>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i data-lucide="save"></i> Uložit změny
    </button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">Zrušit</a>
  </div>
</form>

<!-- Existing photos -->
<?php if (!empty($bike->getPhotos())): ?>
<div class="card mt-xl">
  <div class="card-header">
    <h3>Současné fotografie</h3>
  </div>
  <div class="card-body">
    <div class="existing-photos-grid">
      <?php foreach ($bike->getPhotos() as $photo): ?>
        <div class="existing-photo-card <?= $photo->isPrimary() ? 'primary-photo' : '' ?>">
          <img src="<?= e($photo->getUrl()) ?>" alt="Foto kola">
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
            <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/delete"
                  onsubmit="return confirm('Opravdu smazat tuto fotku?')" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="photo-action-btn photo-action-danger" title="Smazat fotku">
                <i data-lucide="trash-2"></i>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Danger zone: delete bike -->
<div class="danger-zone">
  <h3><i data-lucide="alert-triangle" style="width:18px;height:18px;display:inline"></i> Nebezpečná zóna</h3>
  <p>Smazáním kola se trvale odstraní všechny údaje, fotografie, rezervace a hlášení spojené s tímto kolem.</p>
  <form method="POST" action="/bike/<?= $bike->getId() ?>/delete"
        onsubmit="return confirm('Opravdu chcete trvale smazat toto kolo? Tuto akci nelze vrátit.')">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="btn btn-danger">
      <i data-lucide="trash-2"></i> Smazat kolo
    </button>
  </form>
</div>
