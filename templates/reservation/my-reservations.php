<div class="page-header">
  <h1>Moje rezervace</h1>
  <div class="page-header-actions">
    <a href="/shared" class="btn btn-secondary btn-sm">
      <i data-lucide="repeat"></i> Sdilena kola
    </a>
  </div>
</div>

<!-- Overdue alerts -->
<?php if (!empty($overdue)): ?>
  <div class="overdue-banner">
    <i data-lucide="alert-triangle"></i>
    <div>
      <strong>Mate <?= count($overdue) ?> nevracene <?= count($overdue) === 1 ? 'kolo' : 'kola' ?>!</strong>
      <?php foreach ($overdue as $r): ?>
        <div class="mt-sm">
          <a href="/reservation/<?= $r->getId() ?>">
            <?= e($r->getBike() ? $r->getBike()->getFullName() : 'Kolo #' . $r->getBikeId()) ?>
          </a>
          — melo byt vraceno <?= $r->getFormattedDateTo() ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- As owner -->
<section class="mb-xl">
  <h2 class="section-title">Jako majitel <span class="text-muted text-sm" style="font-weight:400">(zadosti o moje kola)</span></h2>

  <?php if (empty($asOwner)): ?>
    <div class="empty-state">
      <i data-lucide="inbox"></i>
      <p>Zatim zadne zadosti o vase kola.</p>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Kolo</th>
            <th>Vypujcitel</th>
            <th>Termin</th>
            <th>Stav</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($asOwner as $r): ?>
            <tr>
              <td>
                <?php if ($r->getBike()): ?>
                  <?= e($r->getBike()->getFullName()) ?>
                <?php else: ?>
                  Kolo #<?= $r->getBikeId() ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($r->getBorrower()): ?>
                  <?= e($r->getBorrower()->getName()) ?>
                <?php endif; ?>
              </td>
              <td class="text-sm"><?= $r->getDateRangeText() ?></td>
              <td>
                <span class="status-badge <?= $r->getStatusClass() ?>">
                  <?= e($r->getStatusLabel()) ?>
                </span>
                <?php if ($r->isOverdue()): ?>
                  <span class="status-badge status-stolen">Po terminu!</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="/reservation/<?= $r->getId() ?>" class="btn btn-sm btn-ghost">Detail</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<!-- As borrower -->
<section>
  <h2 class="section-title">Jako vypujcitel <span class="text-muted text-sm" style="font-weight:400">(moje zadosti)</span></h2>

  <?php if (empty($asBorrower)): ?>
    <div class="empty-state">
      <i data-lucide="search"></i>
      <p>Zatim jste nepozadali o vypujcku zadneho kola.</p>
      <a href="/shared" class="btn btn-primary btn-sm">
        <i data-lucide="repeat"></i> Prohlednout sdilena kola
      </a>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Kolo</th>
            <th>Majitel</th>
            <th>Termin</th>
            <th>Stav</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($asBorrower as $r): ?>
            <tr>
              <td>
                <?php if ($r->getBike()): ?>
                  <?= e($r->getBike()->getFullName()) ?>
                <?php else: ?>
                  Kolo #<?= $r->getBikeId() ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($r->getOwner()): ?>
                  <?= e($r->getOwner()->getName()) ?>
                <?php endif; ?>
              </td>
              <td class="text-sm"><?= $r->getDateRangeText() ?></td>
              <td>
                <span class="status-badge <?= $r->getStatusClass() ?>">
                  <?= e($r->getStatusLabel()) ?>
                </span>
              </td>
              <td>
                <a href="/reservation/<?= $r->getId() ?>" class="btn btn-sm btn-ghost">Detail</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
