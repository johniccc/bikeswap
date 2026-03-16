<div class="main-public-padded">
<div class="page-header">
  <h1>Nahlásit nález kola</h1>
</div>

<!-- Bike summary -->
<?php
  $extraHtml = '<span class="status-badge status-stolen">Odcizené</span>';
  include __DIR__ . '/../partials/bike-card.php';
  $extraHtml = '';
?>

<form method="POST" action="/found/report/<?= e($bike->getQrHash()) ?>" class="max-w-lg" id="found-report-form">
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
               placeholder="Na tento e-mail vám přijde odkaz ke konverzaci" value="<?= e(old('reporter_email')) ?>">
      </div>

      <div class="form-group">
        <label for="reporter_phone">Telefon (nepovinné)</label>
        <input type="tel" id="reporter_phone" name="reporter_phone"
               placeholder="Pro rychlejší kontakt" value="<?= e(old('reporter_phone')) ?>">
      </div>

      <?php if (!empty($turnstileSiteKey)): ?>
        <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>" data-callback="onTurnstileSuccess"></div>
        <script>function onTurnstileSuccess() { window._turnstileReady = true; }</script>
      <?php endif; ?>
    </fieldset>
  <?php else: ?>
    <div class="alert alert-info mb-lg">
      <i data-lucide="user-check"></i>
      <span>Nahlašujete nález jako <strong><?= e($currentUser->getFullName()) ?></strong> (<?= e($currentUser->getEmail()) ?>).</span>
    </div>
  <?php endif; ?>

  <fieldset>
    <legend>Informace o nálezu</legend>

    <div class="form-group">
      <label for="found_date">Datum nálezu</label>
      <input type="date" id="found_date" name="found_date"
             value="<?= e(old('found_date', date('Y-m-d'))) ?>" max="<?= date('Y-m-d') ?>">
    </div>

    <?php
      $geoPrefix      = 'found_location';
      $geoLabel       = 'Místo nálezu *';
      $geoPlaceholder = 'např. Pardubice, park u Labe, u lavičky';
      $geoValue       = old('found_location_text');
      include __DIR__ . '/../partials/geolocation-input.php';
    ?>

    <div class="form-group">
      <label for="description">Popis situace</label>
      <textarea id="description" name="description" rows="4"
                placeholder="Popište okolnosti nálezu - kde přesně kolo stojí/leželo, v jakém je stavu, jestli je zamčené..."><?= e(old('description')) ?></textarea>
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
  <script>
  document.getElementById('found-report-form').addEventListener('submit', function(e) {
      if (!window._turnstileReady) { e.preventDefault(); alert('Počkejte na dokončení ověření proti botům.'); }
  });
  </script>
<?php endif; ?>
