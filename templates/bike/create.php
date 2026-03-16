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
               placeholder="např. Trek, Giant, Specialized" value="<?= e(old('brand')) ?>">
      </div>
      <div class="form-group">
        <label for="model">Model</label>
        <input type="text" id="model" name="model" maxlength="100"
               placeholder="např. Marlin 7, Defy Advanced" value="<?= e(old('model')) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="color">Barva *</label>
        <input type="text" id="color" name="color" required maxlength="50"
               placeholder="např. černá, červeno-bílá" value="<?= e(old('color')) ?>">
      </div>
      <div class="form-group">
        <label for="year_of_manufacture">Rok výroby</label>
        <input type="number" id="year_of_manufacture" name="year_of_manufacture"
               min="1950" max="<?= date('Y') ?>" value="<?= e(old('year_of_manufacture')) ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="frame_number">Sériové číslo</label>
      <input type="text" id="frame_number" name="frame_number" maxlength="100"
             placeholder="Najdete na spodní straně rámu" value="<?= e(old('frame_number')) ?>">
    </div>

    <div class="form-group">
      <label for="description">Popis</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Doplňující informace o kole (příslušenství, úpravy, zvláštní znaky...)"><?= e(old('description')) ?></textarea>
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

  <fieldset id="sharing-section">
    <legend>Sdílení kola</legend>

    <label class="toggle-row" for="is_shared">
      <span class="toggle-row-text">
        <span class="toggle-row-label"><i data-lucide="share-2" class="toggle-row-icon"></i> Nabídnout k výpůjčce</span>
        <span class="toggle-row-desc">Kolo se zobrazí v katalogu sdílených kol.</span>
      </span>
      <input type="checkbox" name="is_shared" value="1" id="is_shared" class="toggle-switch"
             <?= old('is_shared') ? 'checked' : '' ?>>
      <span class="toggle-track"><span class="toggle-thumb"></span></span>
    </label>

    <div id="sharing-options" class="sharing-subsection" style="display:none">
      <label class="toggle-row" for="auto_accept">
        <span class="toggle-row-text">
          <span class="toggle-row-label"><i data-lucide="zap" class="toggle-row-icon"></i> Automatické schvalování</span>
          <span class="toggle-row-desc">Rezervace v dostupných termínech budou schváleny bez vašeho zásahu.</span>
        </span>
        <input type="checkbox" name="auto_accept" value="1" id="auto_accept" class="toggle-switch"
               <?= old('auto_accept') ? 'checked' : '' ?>>
        <span class="toggle-track"><span class="toggle-thumb"></span></span>
      </label>

      <div id="availability-options" class="availability-panel" style="display:none">
        <div class="form-group">
          <label class="form-group-label"><i data-lucide="calendar-days" style="width:15px;height:15px;display:inline;vertical-align:-2px;margin-right:0.25rem"></i> Dostupné dny</label>
          <p class="form-text">Dny v týdnu, kdy je kolo k dispozici. Bez výběru = všechny dny.</p>
          <div class="day-picker">
            <?php
              $dayNames = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
              for ($i = 1; $i <= 7; $i++):
            ?>
              <label class="day-toggle">
                <input type="checkbox" name="days[]" value="<?= $i ?>">
                <span class="day-toggle-label"><?= $dayNames[$i - 1] ?></span>
              </label>
            <?php endfor; ?>
          </div>
        </div>

        <hr class="availability-divider">

        <div class="form-group">
          <label class="form-group-label"><i data-lucide="calendar-x" style="width:15px;height:15px;display:inline;vertical-align:-2px;margin-right:0.25rem"></i> Vyloučená data</label>
          <p class="form-text">Konkrétní dny, kdy kolo nebude k dispozici (dovolená, oprava...).</p>
          <div class="excluded-date-input-row">
            <input type="date" id="exclude-date-input" min="<?= date('Y-m-d') ?>">
            <button type="button" id="add-exclude-btn" class="btn btn-ghost btn-sm">
              <i data-lucide="plus"></i> Přidat
            </button>
          </div>
          <div id="excluded-dates-list" class="excluded-dates-tags"></div>
        </div>
      </div>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg">
      <i data-lucide="plus"></i> Zaregistrovat kolo
    </button>
    <a href="/dashboard" class="btn btn-ghost">Zrušit</a>
  </div>
</form>

<script src="/js/bike-sharing.js"></script>
