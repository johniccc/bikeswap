<div class="page-header">
  <h1>Nahlasit kradez kola</h1>
  <div class="page-header-actions">
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpet na detail
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
            Ram: <?= e($bike->getFrameNumber()) ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<form method="POST" action="/theft/report/<?= $bike->getId() ?>" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <fieldset>
    <legend>Informace o kradezi</legend>

    <div class="form-group">
      <label for="theft_date">Datum kradeze</label>
      <input type="date" id="theft_date" name="theft_date"
             value="<?= date('Y-m-d') ?>"
             <?php if ($bike->getYearOfManufacture()): ?>min="<?= e($bike->getYearOfManufacture()) ?>-01-01"<?php endif; ?>
             max="<?= date('Y-m-d') ?>">
    </div>

    <div class="form-group">
      <label for="theft_location_text">Misto kradeze *</label>
      <input type="text" id="theft_location_text" name="theft_location_text" required
             placeholder="napr. Pardubice, ul. Karla IV., u nadrazi">
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
      <label for="description">Popis okolnosti</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Popiste okolnosti kradeze - kde bylo kolo zamcene, jakym zamkem, kdy jste si kradeze vsimli..."></textarea>
    </div>

    <div class="form-group">
      <label for="police_case_number">Cislo pripadu u policie</label>
      <input type="text" id="police_case_number" name="police_case_number"
             placeholder="Pokud jste kradez nahlasili na policii">
    </div>
  </fieldset>

  <div class="alert alert-warning">
    <i data-lucide="alert-triangle"></i>
    <div>
      <strong>Upozorneni:</strong> Po odeslani bude vase kolo oznaceno jako <strong>odcizene</strong>
      a zobrazi se ve verejne databazi odcizenych kol. Ostatni uzivatele budou moci nahlasit jeho nalez.
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-danger btn-lg">
      <i data-lucide="alert-circle"></i> Nahlasit kradez
    </button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">Zrusit</a>
  </div>
</form>
