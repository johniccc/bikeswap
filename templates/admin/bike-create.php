<div class="page-header">
  <h1>Přidat kolo</h1>
  <div class="page-header-actions">
    <a href="/admin/bikes" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na kola
    </a>
  </div>
</div>

<form method="POST" action="/admin/bikes/new" enctype="multipart/form-data" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Vlastník</legend>
    <div class="form-group">
      <label for="owner_id">Uživatel *</label>
      <select id="owner_id" name="owner_id" required class="form-control">
        <option value="">— Vyberte uživatele —</option>
        <?php foreach ($users as $u): ?>
          <?php if ($u->getRole() !== 'police'): ?>
            <option value="<?= $u->getId() ?>"
              <?= $u->getId() === $preselectedOwnerId ? 'selected' : '' ?>>
              <?= e($u->getName()) ?> (<?= e($u->getEmail()) ?>)
            </option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>
  </fieldset>

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
               placeholder="např. Marlin 7">
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
                placeholder="Doplňující informace o kole"></textarea>
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
      <i data-lucide="plus"></i> Vytvořit kolo
    </button>
    <a href="/admin/bikes" class="btn btn-ghost">Zrušit</a>
  </div>
</form>
