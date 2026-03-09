<?php
$bike = $reservation->getBike();
$borrower = $reservation->getBorrower();
?>

<div class="page-header">
  <h1>Nahlásit nevrácení kola</h1>
  <div class="page-header-actions">
    <a href="/reservation/<?= $reservation->getId() ?>" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na rezervaci
    </a>
  </div>
</div>

<div class="card mb-lg">
  <div class="card-body">
    <div class="alert alert-warning mb-lg">
      <i data-lucide="alert-triangle"></i>
      <div>
        <strong>Toto je vážný krok.</strong>
        <p class="mt-xs">Nahlášení nevrácení kola bude řešeno správcem. Pokud se prokáže, že nahlášení bylo nepravdivé, ztratíte karmu. Vypůjčitel bude mít možnost podat námitku s důkazy.</p>
      </div>
    </div>

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
            <?php if ($borrower): ?>
              — Vypůjčitel: <?= e($borrower->getName()) ?>
            <?php endif; ?>
          </p>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="/reservation/<?= $reservation->getId() ?>/not-returned">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="reason">Popis situace <span class="text-danger">*</span></label>
        <textarea id="reason" name="reason" rows="5" required
                  placeholder="Popište, co se stalo. Například: vypůjčitel přestal komunikovat, kolo nebylo vráceno v dohodnutém termínu, pokusili jste se ho kontaktovat..."></textarea>
        <p class="form-help text-muted text-sm mt-xs">
          Uveďte co nejvíce relevantních informací. Tyto údaje uvidí správce při řešení případu.
        </p>
      </div>

      <div class="flex gap-sm">
        <button type="submit" class="btn btn-danger"
                data-confirm="Opravdu chcete nahlásit nevrácení kola? Tento krok nelze vzít zpět.">
          <i data-lucide="alert-circle"></i> Nahlásit nevrácení
        </button>
        <a href="/reservation/<?= $reservation->getId() ?>" class="btn btn-ghost">Zrušit</a>
      </div>
    </form>
  </div>
</div>
