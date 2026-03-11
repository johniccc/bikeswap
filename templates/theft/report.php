<div class="page-header">
  <h1>Nahlásit krádež kola</h1>
  <div class="page-header-actions">
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na detail
    </a>
  </div>
</div>

<!-- Bike summary -->
<div class="card mb-lg">
  <div class="card-body">
    <div class="flex gap-lg items-center flex-wrap">
      <?php $photo = $bike->getPrimaryPhoto(); ?>
      <?php if ($photo): ?>
        <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
             style="width:100px;height:75px;object-fit:cover;border-radius:var(--radius-md)">
      <?php endif; ?>
      <div>
        <h3 style="margin-bottom:0.15rem"><?= e($bike->getFullName()) ?></h3>
        <p class="text-muted text-sm">
          <i data-lucide="palette" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
          <?= e($bike->getColor()) ?>
        </p>
        <?php if ($bike->getFrameNumber()): ?>
          <p class="text-muted text-sm">
            <i data-lucide="hash" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
            SČ: <?= e($bike->getFrameNumber()) ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<form method="POST" action="/theft/report/<?= $bike->getId() ?>" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Informace o krádeži</legend>

    <div class="form-group">
      <label for="theft_date">Datum krádeže</label>
      <input type="date" id="theft_date" name="theft_date"
             value="<?= e(old('theft_date', date('Y-m-d'))) ?>"
             <?php if ($bike->getYearOfManufacture()): ?>min="<?= e($bike->getYearOfManufacture()) ?>-01-01"<?php endif; ?>
             max="<?= date('Y-m-d') ?>">
    </div>

    <div class="form-group">
      <label for="theft_location_text">Místo krádeže *</label>
      <input type="text" id="theft_location_text" name="theft_location_text" required
             placeholder="např. Pardubice, ul. Karla IV., u nádraží" value="<?= e(old('theft_location_text')) ?>">
    </div>

    <!-- Hidden GPS fields -->
    <input type="hidden" id="theft_location_lat" name="theft_location_lat">
    <input type="hidden" id="theft_location_lng" name="theft_location_lng">

    <div class="form-group">
      <button type="button" class="btn btn-ghost btn-sm" data-geolocate
              data-lat-input="theft_location_lat" data-lng-input="theft_location_lng">
        <i data-lucide="map-pin"></i> Zjistit moji polohu
      </button>
      <small class="geo-status text-muted text-sm"></small>
    </div>

    <div class="form-group">
      <label for="description">Popis okolností</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Popište okolnosti krádeže - kde bylo kolo zamčené, jakým zámkem, kdy jste si krádeže všimli..."><?= e(old('description')) ?></textarea>
    </div>

    <div class="form-group">
      <label for="police_case_number">Číslo případu u policie</label>
      <input type="text" id="police_case_number" name="police_case_number"
             placeholder="Pokud jste krádež nahlásili na policii" value="<?= e(old('police_case_number')) ?>">
    </div>
  </fieldset>

  <div class="alert alert-warning">
    <i data-lucide="alert-triangle"></i>
    <div>
      <strong>Upozornění:</strong> Po odeslání bude vaše kolo označeno jako <strong>odcizené</strong>
      a zobrazí se ve veřejné databázi odcizených kol. Ostatní uživatelé budou moci nahlásit jeho nález.
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-danger btn-lg">
      <i data-lucide="alert-circle"></i> Nahlásit krádež
    </button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">Zrušit</a>
  </div>
</form>
