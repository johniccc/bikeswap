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

  <fieldset id="sharing-section">
    <legend>Sdílení kola</legend>

    <!-- Share toggle -->
    <label class="toggle-row" for="is_shared">
      <span class="toggle-row-text">
        <span class="toggle-row-label"><i data-lucide="share-2" class="toggle-row-icon"></i> Nabídnout k výpůjčce</span>
        <span class="toggle-row-desc">Kolo se zobrazí v katalogu sdílených kol.</span>
      </span>
      <input type="checkbox" name="is_shared" value="1" id="is_shared" class="toggle-switch"
             <?= $bike->isShared() ? 'checked' : '' ?>>
      <span class="toggle-track"><span class="toggle-thumb"></span></span>
    </label>

    <!-- Auto-accept sub-section -->
    <div id="sharing-options" class="sharing-subsection" style="display:none">
      <label class="toggle-row" for="auto_accept">
        <span class="toggle-row-text">
          <span class="toggle-row-label"><i data-lucide="zap" class="toggle-row-icon"></i> Automatické schvalování</span>
          <span class="toggle-row-desc">Rezervace v dostupných termínech budou schváleny bez vašeho zásahu.</span>
        </span>
        <input type="checkbox" name="auto_accept" value="1" id="auto_accept" class="toggle-switch"
               <?= $bike->isAutoAccept() ? 'checked' : '' ?>>
        <span class="toggle-track"><span class="toggle-thumb"></span></span>
      </label>

      <!-- Availability configuration -->
      <div id="availability-options" class="availability-panel" style="display:none">

        <!-- Day picker -->
        <div class="form-group">
          <label class="form-group-label"><i data-lucide="calendar-days" style="width:15px;height:15px;display:inline;vertical-align:-2px;margin-right:0.25rem"></i> Dostupné dny</label>
          <p class="form-text">Dny v týdnu, kdy je kolo k dispozici. Bez výběru = všechny dny.</p>
          <div class="day-picker">
            <?php
              $dayNames = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
              $currentDays = $bike->getAvailabilityDaysArray();
              $hasCustomDays = $bike->getAvailabilityDays() !== null && $bike->getAvailabilityDays() !== '';
              for ($i = 1; $i <= 7; $i++):
                $checked = $hasCustomDays ? in_array($i, $currentDays) : false;
            ?>
              <label class="day-toggle">
                <input type="checkbox" name="days[]" value="<?= $i ?>" <?= $checked ? 'checked' : '' ?>>
                <span class="day-toggle-label"><?= $dayNames[$i - 1] ?></span>
              </label>
            <?php endfor; ?>
          </div>
        </div>

        <hr class="availability-divider">

        <!-- Excluded dates -->
        <div class="form-group">
          <label class="form-group-label"><i data-lucide="calendar-x" style="width:15px;height:15px;display:inline;vertical-align:-2px;margin-right:0.25rem"></i> Vyloučená data</label>
          <p class="form-text">Konkrétní dny, kdy kolo nebude k dispozici (dovolená, oprava...).</p>
          <div class="excluded-date-input-row">
            <input type="date" id="exclude-date-input" min="<?= date('Y-m-d') ?>">
            <button type="button" id="add-exclude-btn" class="btn btn-ghost btn-sm">
              <i data-lucide="plus"></i> Přidat
            </button>
          </div>
          <div id="excluded-dates-list" class="excluded-dates-tags">
            <?php foreach ($excludedDates as $date):
              $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
              $display = $d ? (int)$d->format('j') . '. ' . (int)$d->format('n') . '. ' . $d->format('Y') : $date;
            ?>
              <span class="excluded-tag" data-date="<?= e($date) ?>">
                <i data-lucide="calendar-x2" class="excluded-tag-icon"></i>
                <?= e($display) ?>
                <button type="button" class="excluded-tag-remove" title="Odebrat">&times;</button>
                <input type="hidden" name="excluded_dates[]" value="<?= e($date) ?>">
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
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
            <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/delete" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="photo-action-btn photo-action-danger" title="Smazat fotku"
                      data-confirm="Opravdu smazat tuto fotku?">
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

<script>
(function() {
    'use strict';
    var isShared = document.getElementById('is_shared');
    var sharingOpts = document.getElementById('sharing-options');
    var autoAccept = document.getElementById('auto_accept');
    var availOpts = document.getElementById('availability-options');
    var addBtn = document.getElementById('add-exclude-btn');
    var dateInput = document.getElementById('exclude-date-input');
    var datesList = document.getElementById('excluded-dates-list');

    function toggleSharing() {
        sharingOpts.style.display = isShared.checked ? '' : 'none';
        if (!isShared.checked) autoAccept.checked = false;
        toggleAutoAccept();
    }

    function toggleAutoAccept() {
        availOpts.style.display = autoAccept.checked ? '' : 'none';
    }

    isShared.addEventListener('change', toggleSharing);
    autoAccept.addEventListener('change', toggleAutoAccept);
    toggleSharing();

    // Format date for display: "3. 4. 2026"
    function fmtDate(iso) {
        var p = iso.split('-');
        return parseInt(p[2]) + '. ' + parseInt(p[1]) + '. ' + p[0];
    }

    function createTag(val) {
        var span = document.createElement('span');
        span.className = 'excluded-tag';
        span.setAttribute('data-date', val);
        // Icon placeholder (lucide will render it)
        var icon = document.createElement('i');
        icon.setAttribute('data-lucide', 'calendar-x2');
        icon.className = 'excluded-tag-icon';
        span.appendChild(icon);
        span.appendChild(document.createTextNode(' ' + fmtDate(val) + ' '));
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'excluded-tag-remove';
        removeBtn.title = 'Odebrat';
        removeBtn.textContent = '\u00d7';
        removeBtn.addEventListener('click', function() { span.remove(); });
        span.appendChild(removeBtn);
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'excluded_dates[]';
        hidden.value = val;
        span.appendChild(hidden);
        datesList.appendChild(span);
        // Re-render lucide icons
        if (window.lucide) lucide.createIcons();
    }

    addBtn.addEventListener('click', function() {
        var val = dateInput.value;
        if (!val) return;
        if (datesList.querySelector('[data-date="'+val+'"]')) return;
        createTag(val);
        dateInput.value = '';
    });

    datesList.querySelectorAll('.excluded-tag-remove').forEach(function(btn) {
        btn.addEventListener('click', function() { btn.parentElement.remove(); });
    });
})();
</script>

<!-- Danger zone: delete bike -->
<div class="danger-zone">
  <h3><i data-lucide="alert-triangle" style="width:18px;height:18px;display:inline"></i> Nebezpečná zóna</h3>
  <p>Smazáním kola se trvale odstraní všechny údaje, fotografie, rezervace a hlášení spojené s tímto kolem.</p>
  <form method="POST" action="/bike/<?= $bike->getId() ?>/delete">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <button type="submit" class="btn btn-danger"
            data-confirm="Opravdu chcete trvale smazat toto kolo? Tuto akci nelze vrátit.">
      <i data-lucide="trash-2"></i> Smazat kolo
    </button>
  </form>
</div>
