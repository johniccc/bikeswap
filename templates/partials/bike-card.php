<?php
/**
 * Bike summary card partial.
 *
 * Expected variables:
 *   $bike        — Bike entity (required)
 *   $statusBadge — Optional HTML string for a status badge (already escaped)
 *   $bikeLink    — Optional bool, wrap name in <a> link (default false)
 *   $extraHtml   — Optional HTML to append inside the info <div> (already escaped)
 */
$statusBadge = $statusBadge ?? '';
$bikeLink    = $bikeLink ?? false;
$extraHtml   = $extraHtml ?? '';
?>
<div class="card mb-lg">
  <div class="card-body">
    <div class="flex gap-lg items-center flex-wrap">
      <?php $photo = $bike->getPrimaryPhoto(); ?>
      <?php if ($photo): ?>
        <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
             style="width:100px;height:75px;object-fit:cover;border-radius:var(--radius-md)">
      <?php endif; ?>
      <div>
        <h3 style="margin-bottom:0.15rem">
          <?php if ($bikeLink): ?>
            <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
          <?php else: ?>
            <?= e($bike->getFullName()) ?>
          <?php endif; ?>
        </h3>
        <p class="text-muted text-sm">
          <i data-lucide="palette" class="icon-inline"></i>
          <?= e($bike->getColor()) ?>
        </p>
        <?php if ($bike->getFrameNumber()): ?>
          <p class="text-muted text-sm">
            <i data-lucide="hash" class="icon-inline"></i>
            SČ: <?= e($bike->getFrameNumber()) ?>
          </p>
        <?php endif; ?>
        <?= $extraHtml ?>
      </div>
      <?php if ($statusBadge !== ''): ?>
        <div style="margin-left:auto">
          <?= $statusBadge ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
