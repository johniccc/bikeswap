<div class="page-header">
  <h1>Upravit kolo</h1>
  <div class="page-header-actions">
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">
      <i data-lucide="arrow-left"></i> Zpet na detail
    </a>
  </div>
</div>

<form method="POST" action="/bike/<?= $bike->getId() ?>/edit" enctype="multipart/form-data" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Zakladni udaje</legend>

    <div class="form-row">
      <div class="form-group">
        <label for="brand">Znacka *</label>
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
        <label for="year_of_manufacture">Rok vyroby</label>
        <input type="number" id="year_of_manufacture" name="year_of_manufacture"
               min="1950" max="<?= date('Y') ?>"
               value="<?= $bike->getYearOfManufacture() ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="frame_number">Cislo ramu</label>
      <input type="text" id="frame_number" name="frame_number" maxlength="100"
             value="<?= e($bike->getFrameNumber() ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="description">Popis</label>
      <textarea id="description" name="description" rows="4"><?= e($bike->getDescription() ?? '') ?></textarea>
    </div>
  </fieldset>

  <fieldset>
    <legend>Sdileni</legend>
    <div class="form-check">
      <input type="checkbox" name="is_shared" value="1" id="is_shared"
             <?= $bike->isShared() ? 'checked' : '' ?>>
      <label for="is_shared">Nabidnout kolo k vypujcce ostatnim uzivatelum</label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Pridat nove fotografie</legend>
    <div class="form-group">
      <label class="form-file-label" for="photo-input">
        <i data-lucide="image-plus"></i> Vybrat fotografie
      </label>
      <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" style="display:none">
      <div class="photo-preview-grid" id="photo-preview"></div>
      <input type="hidden" name="primary_index" id="primary-index" value="0">
      <p class="form-text">Podporovane formaty: JPG, PNG, WebP.</p>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <i data-lucide="save"></i> Ulozit zmeny
    </button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">Zrusit</a>
  </div>
</form>

<!-- Existing photos -->
<?php if (!empty($bike->getPhotos())): ?>
<div class="card mt-xl">
  <div class="card-header">
    <h3>Soucasne fotografie</h3>
  </div>
  <div class="card-body">
    <div class="existing-photos-grid">
      <?php foreach ($bike->getPhotos() as $photo): ?>
        <div class="existing-photo-card <?= $photo->isPrimary() ? 'primary-photo' : '' ?>">
          <img src="<?= e($photo->getUrl()) ?>" alt="Foto kola">
          <div class="existing-photo-actions">
            <?php if ($photo->isPrimary()): ?>
              <span class="btn btn-sm" style="font-size:0.7rem;opacity:0.6;pointer-events:none">Hlavni</span>
            <?php else: ?>
              <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/primary" style="margin:0">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="btn btn-sm btn-ghost" style="font-size:0.7rem">Nastavit hlavni</button>
              </form>
            <?php endif; ?>
            <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/delete"
                  onsubmit="return confirm('Opravdu smazat tuto fotku?')" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:0.7rem">
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
  <h3><i data-lucide="alert-triangle" style="width:18px;height:18px;display:inline"></i> Nebezpecna zona</h3>
  <p>Smazanim kola se trvale odstrani vsechny udaje, fotografie, rezervace a hlaseni spojene s timto kolem.</p>
  <form method="POST" action="/bike/<?= $bike->getId() ?>/delete"
        onsubmit="return confirm('Opravdu chcete trvale smazat toto kolo? Tuto akci nelze vratit.')">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="btn btn-danger">
      <i data-lucide="trash-2"></i> Smazat kolo
    </button>
  </form>
</div>
