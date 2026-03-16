<?php
$bike = $reservation->getBike();
$owner = $reservation->getOwner();
?>

<div class="page-header">
  <h1>Podat námitku</h1>
  <div class="page-header-actions">
    <a href="/reservation/<?= $reservation->getId() ?>" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na rezervaci
    </a>
  </div>
</div>

<div class="card mb-lg">
  <div class="card-body">
    <div class="alert alert-info mb-lg">
      <i data-lucide="info"></i>
      <div>
        <strong>Vlastník nahlásil, že kolo nebylo vráceno.</strong>
        <p class="mt-xs">Pokud s tím nesouhlasíte, můžete podat námitku s vysvětlením a případnými důkazy (fotografie). Případ bude řešen správcem.</p>
      </div>
    </div>

    <?php if ($reservation->getNotReturnedReason()): ?>
      <div class="card mb-lg" style="background:var(--bg-secondary, #f5f5f0)">
        <div class="card-body">
          <h4 class="mb-xs">
            <i data-lucide="message-square" style="width:16px;height:16px;display:inline;vertical-align:-2px"></i>
            Tvrzení vlastníka
          </h4>
          <p><?= nl2br(e($reservation->getNotReturnedReason())) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($bike): ?>
      <div class="flex gap-lg items-center mb-lg" style="padding-bottom:1rem;border-bottom:1px solid var(--border)">
        <?php $photo = $bike->getPrimaryPhoto(); ?>
        <?php if ($photo): ?>
          <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
               style="width:80px;height:60px;object-fit:cover;border-radius:var(--radius-md)">
        <?php endif; ?>
        <div>
          <h3 style="margin:0"><?= e($bike->getFullName()) ?></h3>
          <p class="text-muted text-sm">
            Termín: <?= $reservation->getDateRangeText() ?>
            <?php if ($owner): ?>
              — Majitel: <?= e($owner->getFullName()) ?>
            <?php endif; ?>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="/reservation/<?= $reservation->getId() ?>/dispute" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="reason">Vaše vysvětlení <span class="text-danger">*</span></label>
        <textarea id="reason" name="reason" rows="5" required
                  placeholder="Popište svou verzi události. Například: kolo bylo vráceno včas, máte důkaz o předání, komunikace probíhala jinak..."></textarea>
        <p class="form-help text-muted text-sm mt-xs">
          Uveďte co nejvíce relevantních informací. Správce uvidí obě strany při rozhodování.
        </p>
      </div>

      <div class="form-group">
        <label for="photos">Důkazní fotografie (volitelné)</label>
        <input type="file" id="photos" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"
               class="form-control">
        <p class="form-help text-muted text-sm mt-xs">
          Můžete přiložit fotografie jako důkaz (např. fotka předání kola, komunikace apod.). Max 5 MB na soubor.
        </p>
        <div id="photo-preview" class="photo-preview-grid mt-sm"></div>
      </div>

      <div class="flex gap-sm">
        <button type="submit" class="btn btn-warning"
                data-confirm="Opravdu chcete podat námitku? Případ bude předán správci k rozhodnutí."
                data-confirm-class="btn-warning"
                data-confirm-ok="Podat námitku">
          <i data-lucide="flag"></i> Podat námitku
        </button>
        <a href="/reservation/<?= $reservation->getId() ?>" class="btn btn-ghost">Zrušit</a>
      </div>
    </form>
  </div>
</div>
