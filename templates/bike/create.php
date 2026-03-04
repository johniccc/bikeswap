<div class="page-header">
  <h1>Registrovat nove kolo</h1>
</div>

<form method="POST" action="/bike/new" enctype="multipart/form-data" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Zakladni udaje</legend>

    <div class="form-row">
      <div class="form-group">
        <label for="brand">Znacka *</label>
        <input type="text" id="brand" name="brand" required maxlength="100"
               placeholder="napr. Trek, Giant, Specialized">
      </div>
      <div class="form-group">
        <label for="model">Model</label>
        <input type="text" id="model" name="model" maxlength="100"
               placeholder="napr. Marlin 7, Defy Advanced">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="color">Barva *</label>
        <input type="text" id="color" name="color" required maxlength="50"
               placeholder="napr. cerna, cerveno-bila">
      </div>
      <div class="form-group">
        <label for="year_of_manufacture">Rok vyroby</label>
        <input type="number" id="year_of_manufacture" name="year_of_manufacture"
               min="1950" max="<?= date('Y') ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="frame_number">Cislo ramu</label>
      <input type="text" id="frame_number" name="frame_number" maxlength="100"
             placeholder="Najdete na spodni strane ramu">
    </div>

    <div class="form-group">
      <label for="description">Popis</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Doplnujici informace o kole (prislusenstvi, upravy, zvlastni znaky...)"></textarea>
    </div>
  </fieldset>

  <fieldset>
    <legend>Fotografie</legend>
    <div class="form-group">
      <label class="form-file-label" for="photo-input">
        <i data-lucide="image-plus"></i> Vybrat fotografie
      </label>
      <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" style="display:none">
      <div class="photo-preview-grid" id="photo-preview"></div>
      <input type="hidden" name="primary_index" id="primary-index" value="0">
      <p class="form-text">Prvni fotografie bude primarni. Podporovane formaty: JPG, PNG, WebP.</p>
    </div>
  </fieldset>

  <fieldset>
    <legend>Sdileni</legend>
    <div class="form-check">
      <input type="checkbox" name="is_shared" value="1" id="is_shared">
      <label for="is_shared">Nabidnout kolo k vypujcce ostatnim uzivatelum</label>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg">
      <i data-lucide="plus"></i> Zaregistrovat kolo
    </button>
    <a href="/dashboard" class="btn btn-ghost">Zrusit</a>
  </div>
</form>
