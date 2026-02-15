<h1>Moje rezervace</h1>

<?php if (isset($session) && $session->hasFlash('success')): ?>
    <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
<?php endif; ?>

<!-- ═══ OVERDUE ALERTS ═══ -->
<?php if (!empty($overdue)): ?>
    <div class="alert alert-error">
        <strong>⚠ Máte <?= count($overdue) ?> nevrácené <?= count($overdue) === 1 ? 'kolo' : 'kola' ?>!</strong>
        <?php foreach ($overdue as $r): ?>
            <div class="mt-1">
                <a href="/reservation/<?= $r->getId() ?>">
                    <?= e($r->getBike() ? $r->getBike()->getFullName() : 'Kolo #' . $r->getBikeId()) ?>
                </a>
                – mělo být vráceno <?= $r->getFormattedDateTo() ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ═══ AS OWNER (incoming requests) ═══ -->
<section class="mb-3">
    <h2>Jako majitel <span class="text-muted text-small">(žádosti o moje kola)</span></h2>

    <?php if (empty($asOwner)): ?>
        <p class="text-muted">Zatím žádné žádosti o vaše kola.</p>
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
                            <td><?= $r->getDateRangeText() ?></td>
                            <td>
                                <span class="badge <?= $r->getStatusClass() ?>">
                                    <?= e($r->getStatusLabel()) ?>
                                </span>
                                <?php if ($r->isOverdue()): ?>
                                    <span class="badge status-stolen">Po termínu!</span>
                                <?php endif; ?>
                            </td>
                            <td><a href="/reservation/<?= $r->getId() ?>" class="btn btn-small">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ═══ AS BORROWER (my requests) ═══ -->
<section>
    <h2>Jako vypůjčitel <span class="text-muted text-small">(moje žádosti)</span></h2>

    <?php if (empty($asBorrower)): ?>
        <p class="text-muted">Zatím jste nepožádali o výpůjčku žádného kola.</p>
        <a href="/shared" class="btn btn-primary">Prohlédnout sdílená kola</a>
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
                            <td><?= $r->getDateRangeText() ?></td>
                            <td>
                                <span class="badge <?= $r->getStatusClass() ?>">
                                    <?= e($r->getStatusLabel()) ?>
                                </span>
                            </td>
                            <td><a href="/reservation/<?= $r->getId() ?>" class="btn btn-small">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>