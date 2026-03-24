<div class="page-header">
  <h1>Rezervovat kolo</h1>
</div>

<!-- Bike preview -->
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
        <p class="text-muted text-sm">Barva: <?= e($bike->getColor()) ?></p>
        <a href="/bike/<?= e($bike->getQrHash()) ?>" class="text-sm">Zobrazit detail kola</a>
      </div>
    </div>
  </div>
</div>

<!-- Calendar -->
<div class="card mb-lg">
  <div class="card-body">
    <h3 class="section-title">Vyberte termín výpůjčky</h3>
    <div class="calendar-legend mb-md">
      <span class="calendar-legend-item">
        <span class="calendar-legend-dot" style="background:var(--primary)"></span> Váš výběr
      </span>
      <span class="calendar-legend-item">
        <span class="calendar-legend-dot" style="background:var(--danger)"></span> Obsazené
      </span>
      <span class="calendar-legend-item">
        <span class="calendar-legend-dot" style="background:var(--primary-light)"></span> Rozsah
      </span>
      <?php if ($bike->isAutoAccept()): ?>
      <span class="calendar-legend-item">
        <span class="calendar-legend-dot" style="background:var(--gray-300, #ccc)"></span> Nedostupné
      </span>
      <?php endif; ?>
    </div>

    <div id="calendar-container" class="calendar-container"></div>
  </div>
</div>

<!-- Reservation form -->
<form method="POST" action="/reservation/new/<?= $bike->getId() ?>" id="reservation-form">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="date_from" id="input-date-from" value="">
  <input type="hidden" name="date_to" id="input-date-to" value="">

  <div class="card mb-lg">
    <div class="card-body">
      <h3 class="section-title">Vybraný termín</h3>
      <p id="selected-range-text" class="text-muted">Klikněte na kalendář pro výběr datumu.</p>

      <div class="form-group mt-lg">
        <label for="message">Zpráva pro majitele (volitelné)</label>
        <textarea id="message" name="message" rows="3"
                  placeholder="Představte se, sdělte účel výpůjčky..."><?= e(old('message')) ?></textarea>
      </div>
    </div>
  </div>

  <?php if (!empty($turnstileSiteKey)): ?>
    <div class="cf-turnstile mb-md" data-sitekey="<?= e($turnstileSiteKey) ?>" data-callback="onTurnstileSuccess"></div>
    <script>function onTurnstileSuccess() { window._turnstileReady = true; }</script>
  <?php else: ?>
    <script>window._turnstileReady = true;</script>
  <?php endif; ?>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>
      <i data-lucide="calendar-check"></i> <?= $bike->isAutoAccept() ? 'Rezervovat kolo' : 'Odeslat žádost o výpůjčku' ?>
    </button>
    <a href="/shared" class="btn btn-ghost">Zpět na seznam</a>
  </div>
</form>

<?php if (!empty($turnstileSiteKey)): ?>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<script>
window.calendarConfig = {
    unavailableDates: <?= json_encode($unavailableDates) ?>,
    availabilityDays: <?= json_encode($availabilityDays) ?>,
    excludedDates: <?= json_encode($excludedDates) ?>
};
</script>
<script src="/js/calendar-widget.js"></script>
