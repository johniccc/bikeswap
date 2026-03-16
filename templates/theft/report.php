<div class="page-header">
  <h1>Nahlásit krádež kola</h1>
  <div class="page-header-actions">
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na detail
    </a>
  </div>
</div>

<!-- Bike summary -->
<?php include __DIR__ . '/../partials/bike-card.php'; ?>

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

    <?php
      $geoPrefix      = 'theft_location';
      $geoLabel       = 'Místo krádeže *';
      $geoPlaceholder = 'např. Pardubice, ul. Karla IV., u nádraží';
      $geoValue       = old('theft_location_text');
      include __DIR__ . '/../partials/geolocation-input.php';
    ?>

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
