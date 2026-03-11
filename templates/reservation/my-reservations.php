<div data-auto-refresh="/reservations/poll" data-refresh-interval="8000"></div>

<div class="page-header">
  <h1>Moje rezervace</h1>
  <div class="page-header-actions">
    <a href="/shared" class="btn btn-secondary btn-sm">
      <i data-lucide="repeat"></i> Sdílená kola
    </a>
  </div>
</div>

<!-- Overdue alerts -->
<?php if (!empty($overdue)): ?>
  <div class="overdue-banner">
    <i data-lucide="alert-triangle"></i>
    <div>
      <strong>Máte <?= count($overdue) ?> nevrácené <?= count($overdue) === 1 ? 'kolo' : 'kola' ?>!</strong>
      <?php foreach ($overdue as $r): ?>
        <div class="mt-sm">
          <a href="/reservation/<?= $r->getId() ?>">
            <?= e($r->getBike() ? $r->getBike()->getFullName() : 'Kolo #' . $r->getBikeId()) ?>
          </a>
          — mělo být vráceno <?= $r->getFormattedDateTo() ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- As owner -->
<section class="mb-xl">
  <h2 class="section-title">Jako majitel <span class="text-muted text-sm" style="font-weight:400">(žádosti o moje kola)</span></h2>
  <?php if (!empty($ownerBikes)): ?>
    <form method="GET" action="/reservations" class="bike-filter-form mb-md">
      <label for="bike-filter-select" class="bike-filter-label">
        <i data-lucide="filter" style="width:12px;height:12px;display:inline;vertical-align:-1px"></i>
        Filtrovat dle kola
      </label>
      <select name="bike" id="bike-filter-select" class="bike-filter-select<?= $filterBike ? ' bike-filter-active' : '' ?>" onchange="this.form.submit()">
        <option value="">Všechna kola</option>
        <?php foreach ($ownerBikes as $b): ?>
          <option value="<?= $b->getId() ?>" <?= ($filterBike && $filterBike->getId() === $b->getId()) ? 'selected' : '' ?>>
            <?= e($b->getFullName()) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>

  <?php if (empty($asOwner)): ?>
    <div class="empty-state">
      <i data-lucide="inbox"></i>
      <p>Zatím žádné žádosti o vaše kola.</p>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Kolo</th>
            <th>Vypůjčitel</th>
            <th>Termín</th>
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
                  <span class="status-badge status-stolen">Po termínu!</span>
                <?php endif; ?>
                <?php if ($r->isCompleted() && !in_array($r->getId(), $reviewedIds, true)): ?>
                  <span class="status-badge status-review">Nehodnoceno</span>
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
  <h2 class="section-title">Jako vypůjčitel <span class="text-muted text-sm" style="font-weight:400">(moje žádosti)</span></h2>

  <?php if (empty($asBorrower)): ?>
    <div class="empty-state">
      <i data-lucide="search"></i>
      <p>Zatím jste nepožádali o výpůjčku žádného kola.</p>
      <a href="/shared" class="btn btn-primary btn-sm">
        <i data-lucide="repeat"></i> Prohlédnout sdílená kola
      </a>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Kolo</th>
            <th>Majitel</th>
            <th>Termín</th>
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
                <?php if ($r->isCompleted() && !in_array($r->getId(), $reviewedIds, true)): ?>
                  <span class="status-badge status-review">Nehodnoceno</span>
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
