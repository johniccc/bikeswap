<div class="main-public-padded">
<div class="page-header">
  <h1>Nahlásit nález kola</h1>
</div>

<!-- Bike summary -->
<div class="card mb-lg">
  <div class="card-body">
    <div class="flex gap-lg items-center flex-wrap">
      <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
      <?php if ($primaryPhoto): ?>
        <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
             style="width:100px;height:75px;object-fit:cover;border-radius:var(--radius-md)">
      <?php endif; ?>
      <div>
        <h3 style="margin-bottom:0.15rem"><?= e($bike->getFullName()) ?></h3>
        <p class="text-muted text-sm">
          <i data-lucide="palette" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
          <?= e($bike->getColor()) ?>
        </p>
        <span class="status-badge status-stolen">Odcizené</span>
      </div>
    </div>
  </div>
</div>

<form method="POST" action="/found/report/<?= e($bike->getQrHash()) ?>" class="max-w-lg">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

  <!-- Honeypot (anti-bot, hidden via CSS) -->
  <div class="sr-only" aria-hidden="true">
    <label for="website">Website</label>
    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
  </div>

  <?php if ($currentUser === null): ?>
    <fieldset>
      <legend>Vaše kontaktní údaje</legend>
      <p class="text-muted text-sm mb-md">
        Vaše údaje nebudou zobrazeny veřejně. Slouží pouze pro komunikaci s majitelem kola.
        <a href="/login?redirect=<?= urlencode('/found/report/' . $bike->getQrHash()) ?>">Přihlaste se</a> pro jednodušší nahlášení.
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
        <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>"></div>
      <?php endif; ?>
    </fieldset>
  <?php else: ?>
    <div class="alert alert-info mb-lg">
      <i data-lucide="user-check"></i>
      <span>Nahlašujete nález jako <strong><?= e($currentUser->getName()) ?></strong> (<?= e($currentUser->getEmail()) ?>).</span>
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
      <button type="button" class="btn btn-ghost btn-sm" data-geolocate
              data-lat-input="found_location_lat" data-lng-input="found_location_lng">
        <i data-lucide="map-pin"></i> Zjistit moji polohu
      </button>
      <small class="geo-status text-muted text-sm"></small>
    </div>

    <div class="form-group">
      <label for="description">Popis situace</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Popište okolnosti nálezu - kde přesně kolo stojí/leželo, v jakém je stavu, jestli je zamčené..."></textarea>
    </div>
  </fieldset>

  <div class="alert alert-info mb-lg">
    <i data-lucide="info"></i>
    <div>
      <strong>Co se stane dál?</strong> Majitel kola bude okamžitě upozorněn na váš nález.
      Budete přesměrováni do anonymní konverzace, kde se můžete domluvit na předání kola.
      <?php if ($currentUser === null): ?>
        Odkaz ke konverzaci vám přijde také na e-mail — uložte si ho.
      <?php endif; ?>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg">
      <i data-lucide="map-pin"></i> Nahlásit nález
    </button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost">Zrušit</a>
  </div>
</form>

</div><!-- .main-public-padded -->

<?php if (!empty($turnstileSiteKey)): ?>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
