<div class="found-report">
    <h1>Nahlásit nález kola</h1>

    <?php if (isset($session) && $session->hasFlash('error')): ?>
        <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
    <?php endif; ?>

    <?php if (isset($session) && $session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
    <?php endif; ?>

    <!-- Bike summary so the finder knows which bike they're reporting -->
    <div class="bike-summary">
        <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
        <?php if ($primaryPhoto): ?>
            <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-summary-photo">
        <?php endif; ?>
        <div class="bike-summary-info">
            <h2><?= e($bike->getFullName()) ?></h2>
            <p>Barva: <?= e($bike->getColor()) ?></p>
            <span class="status-badge status-stolen">Odcizené</span>
        </div>
    </div>

    <form method="POST" action="/found/report/<?= e($bike->getQrHash()) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <!-- Honeypot (anti-bot, hidden via CSS) -->
        <div class="form-group" style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <?php if ($currentUser === null): ?>
            <fieldset>
                <legend>Vaše kontaktní údaje</legend>
                <p class="fieldset-hint">
                    Vaše údaje nebudou zobrazeny veřejně. Slouží pouze pro komunikaci s majitelem kola.
                    <a href="/login">Přihlaste se</a> pro jednodušší nahlášení.
                </p>

                <div class="form-group">
                    <label for="reporter_email">E-mail *</label>
                    <input type="email" id="reporter_email" name="reporter_email" required
                           placeholder="Na tento e-mail vám přijde odkaz ke konverzaci">
                </div>

                <div class="form-group">
                    <label for="reporter_phone">Telefon (nepovinné)</label>
                    <input type="tel" id="reporter_phone" name="reporter_phone"
                           placeholder="Pro rychlejší kontakt">
                </div>

                <?php if (!empty($turnstileSiteKey)): ?>
                    <!-- Cloudflare Turnstile CAPTCHA for anonymous users -->
                    <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>"></div>
                <?php endif; ?>
            </fieldset>
        <?php else: ?>
            <div class="alert alert-info">
                Nahlašujete nález jako <strong><?= e($currentUser->getName()) ?></strong>
                (<?= e($currentUser->getEmail()) ?>).
            </div>
        <?php endif; ?>

        <fieldset>
            <legend>Informace o nálezu</legend>

            <div class="form-group">
                <label for="found_date">Datum nálezu</label>
                <input type="date" id="found_date" name="found_date"
                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label for="found_location_text">Místo nálezu *</label>
                <input type="text" id="found_location_text" name="found_location_text" required
                       placeholder="např. Pardubice, park u Labe, u lavičky">
            </div>

            <!-- Hidden GPS fields -->
            <input type="hidden" id="found_location_lat" name="found_location_lat">
            <input type="hidden" id="found_location_lng" name="found_location_lng">

            <div class="form-group">
                <button type="button" id="geolocate-btn" class="btn btn-small">
                    Zjistit moji polohu
                </button>
                <small id="geo-status"></small>
            </div>

            <div class="form-group">
                <label for="description">Popis situace</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Popište okolnosti nálezu — kde přesně kolo stojí/leželo, v jakém je stavu, jestli je zamčené..."></textarea>
            </div>
        </fieldset>

        <div class="alert alert-info">
            <strong>Co se stane dál?</strong> Majitel kola bude okamžitě upozorněn na váš nález.
            Budete přesměrováni do anonymní konverzace, kde se můžete domluvit na předání kola.
            <?php if ($currentUser === null): ?>
                Odkaz ke konverzaci vám přijde také na e-mail — uložte si ho.
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-success">Nahlásit nález</button>
        <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn">Zrušit</a>
    </form>
</div>

<script>
document.getElementById('geolocate-btn')?.addEventListener('click', function() {
    var status = document.getElementById('geo-status');
    var btn = this;

    if (!navigator.geolocation) {
        status.textContent = 'Geolokace není podporována vaším prohlížečem.';
        return;
    }

    if (window.isSecureContext === false) {
        status.textContent = 'Geolokace vyžaduje HTTPS připojení.';
        return;
    }

    status.textContent = 'Zjišťuji polohu...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('found_location_lat').value = position.coords.latitude;
            document.getElementById('found_location_lng').value = position.coords.longitude;
            status.textContent = 'Poloha zjištěna (' +
                position.coords.latitude.toFixed(5) + ', ' +
                position.coords.longitude.toFixed(5) + ')';
            btn.disabled = false;
        },
        function(error) {
            var messages = {
                1: 'Přístup k poloze byl zamítnut.',
                2: 'Poloha není dostupná.',
                3: 'Zjišťování polohy vypršelo.'
            };
            status.textContent = messages[error.code] || 'Chyba geolokace.';
            btn.disabled = false;
        },
        { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
    );
});
</script>

<?php if (!empty($turnstileSiteKey)): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>