<div class="page-header">
  <h1>Registrovat nové kolo</h1>
</div>

<form method="POST" action="/bike/new" enctype="multipart/form-data" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Základní údaje</legend>

    <div class="form-row">
      <div class="form-group">
        <label for="brand">Značka *</label>
        <input type="text" id="brand" name="brand" required maxlength="100"
               placeholder="např. Trek, Giant, Specialized">
      </div>
      <div class="form-group">
        <label for="model">Model</label>
        <input type="text" id="model" name="model" maxlength="100"
               placeholder="např. Marlin 7, Defy Advanced">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="color">Barva *</label>
        <input type="text" id="color" name="color" required maxlength="50"
               placeholder="např. černá, červeno-bílá">
      </div>
      <div class="form-group">
        <label for="year_of_manufacture">Rok výroby</label>
        <input type="number" id="year_of_manufacture" name="year_of_manufacture"
               min="1950" max="<?= date('Y') ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="frame_number">Sériové číslo</label>
      <input type="text" id="frame_number" name="frame_number" maxlength="100"
             placeholder="Najdete na spodní straně rámu">
    </div>

    <div class="form-group">
      <label for="description">Popis</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Doplňující informace o kole (příslušenství, úpravy, zvláštní znaky...)"></textarea>
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
      <p class="form-text">První fotografie bude primární. Podporované formáty: JPG, PNG, WebP.</p>
    </div>
  </fieldset>

  <fieldset>
    <legend>Sdílení</legend>
    <div class="form-check">
      <input type="checkbox" name="is_shared" value="1" id="is_shared">
      <label for="is_shared">Nabídnout kolo k výpůjčce ostatním uživatelům</label>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg">
      <i data-lucide="plus"></i> Zaregistrovat kolo
    </button>
    <a href="/dashboard" class="btn btn-ghost">Zrušit</a>
  </div>
</form>
