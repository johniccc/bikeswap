<h1>Nahlásit krádež kola</h1>

<div class="bike-summary">
    <h2><?= e($bike->getFullName()) ?></h2>
    <p>Barva: <?= e($bike->getColor()) ?></p>
    <?php if ($bike->getFrameNumber()): ?>
        <p>Číslo rámu: <?= e($bike->getFrameNumber()) ?></p>
    <?php endif; ?>
</div>

<?php if (isset($session) && $session->hasFlash('error')): ?>
    <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
<?php endif; ?>

<form method="POST" action="/theft/report/<?= $bike->getId() ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <fieldset>
        <legend>Informace o krádeži</legend>

        <div class="form-group">
            <label for="theft_date">Datum krádeže</label>
            <input type="date" id="theft_date" name="theft_date"
                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="theft_location_text">Místo krádeže *</label>
            <input type="text" id="theft_location_text" name="theft_location_text" required
                   placeholder="např. Pardubice, ul. Karla IV., u nádraží">
        </div>

        <!-- Hidden GPS fields (filled by JS geolocation if available) -->
        <input type="hidden" id="theft_location_lat" name="theft_location_lat">
        <input type="hidden" id="theft_location_lng" name="theft_location_lng">

        <div class="form-group">
            <button type="button" id="geolocate-btn" class="btn btn-small">
                Zjistit moji polohu
            </button>
            <small id="geo-status"></small>
        </div>

        <div class="form-group">
            <label for="description">Popis okolností</label>
            <textarea id="description" name="description" rows="4"
                      placeholder="Popište okolnosti krádeže - kde bylo kolo zamčené, jakým zámkem, kdy jste si krádeže všimli..."></textarea>
        </div>

        <div class="form-group">
            <label for="police_case_number">Číslo případu u policie</label>
            <input type="text" id="police_case_number" name="police_case_number"
                   placeholder="Pokud jste krádež nahlásili na policii">
        </div>
    </fieldset>

    <div class="alert alert-warning">
        <strong>Upozornění:</strong> Po odeslání bude vaše kolo označeno jako <strong>odcizené</strong>
        a zobrazí se ve veřejné databázi odcizených kol. Ostatní uživatelé budou moci nahlásit jeho nález.
    </div>

    <button type="submit" class="btn btn-danger">Nahlásit krádež</button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn">Zrušit</a>
</form>

<script>
document.getElementById('geolocate-btn')?.addEventListener('click', function() {
    var status = document.getElementById('geo-status');
    var btn = this;

    // Check if geolocation is available at all
    if (!navigator.geolocation) {
        status.textContent = 'Geolokace není podporována vaším prohlížečem.';
        return;
    }

    // Check secure context (HTTPS or localhost)
    if (window.isSecureContext === false) {
        status.textContent = 'Geolokace vyžaduje HTTPS připojení. Na HTTP nefunguje.';
        return;
    }

    status.textContent = 'Zjišťuji polohu...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('theft_location_lat').value = position.coords.latitude;
            document.getElementById('theft_location_lng').value = position.coords.longitude;
            status.textContent = 'Poloha zjištěna (' +
                position.coords.latitude.toFixed(5) + ', ' +
                position.coords.longitude.toFixed(5) + ')';
            btn.disabled = false;
        },
        function(error) {
            var messages = {
                1: 'Přístup k poloze byl zamítnut. Povolte geolokaci v nastavení prohlížeče.',
                2: 'Poloha není dostupná. Zařízení nemohlo zjistit vaši pozici.',
                3: 'Zjišťování polohy vypršelo. Zkuste to znovu.'
            };
            status.textContent = messages[error.code] || ('Neznámá chyba geolokace (kód: ' + error.code + ').');
            btn.disabled = false;
        },
        {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 300000
        }
    );
});
</script>