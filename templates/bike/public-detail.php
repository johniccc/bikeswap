<div class="bike-detail">
    <div class="bike-header">
        <h1><?= e($bike->getFullName()) ?></h1>
        <span class="status-badge <?= e($bike->getStatusClass()) ?>">
            <?= e($bike->getStatusLabel()) ?>
        </span>
    </div>

    <?php if ($bike->isStolen()): ?>
        <div class="alert alert-stolen">
            Toto kolo je hlášeno jako <strong>odcizené</strong>.
            Pokud jste ho nalezli, prosím <a href="/found/report/<?= e($bike->getQrHash()) ?>">nahlaste nález</a>.
        </div>
    <?php endif; ?>

    <?php if (isset($session) && $session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
    <?php endif; ?>

    <!-- Photo gallery -->
    <div class="bike-gallery">
        <?php if (!empty($bike->getPhotos())): ?>
            <?php foreach ($bike->getPhotos() as $photo): ?>
                <img src="<?= e($photo->getUrl()) ?>"
                     alt="<?= e($bike->getFullName()) ?>"
                     class="bike-photo <?= $photo->isPrimary() ? 'primary' : '' ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-photo">Žádné fotografie</p>
        <?php endif; ?>
    </div>

    <!-- Bike info -->
    <div class="bike-info">
        <table>
            <tr>
                <th>Značka</th>
                <td><?= e($bike->getBrand()) ?></td>
            </tr>
            <?php if ($bike->getModel()): ?>
            <tr>
                <th>Model</th>
                <td><?= e($bike->getModel()) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Barva</th>
                <td><?= e($bike->getColor()) ?></td>
            </tr>
            <?php if ($bike->getYearOfManufacture()): ?>
            <tr>
                <th>Rok výroby</th>
                <td><?= $bike->getYearOfManufacture() ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($bike->getDescription()): ?>
            <tr>
                <th>Popis</th>
                <td><?= nl2br(e($bike->getDescription())) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Stav</th>
                <td>
                    <span class="status-badge <?= e($bike->getStatusClass()) ?>">
                        <?= e($bike->getStatusLabel()) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- QR Code -->
    <?php if ($isOwner): ?>
        <div class="bike-qr">
            <h2>QR kód vašeho kola</h2>
            <img src="<?= e($qrDataUri) ?>" alt="QR kód" class="qr-image">
            <p><a href="/file/qr/<?= e($bike->getQrHash()) ?>" download="bikeswap-qr.png">Stáhnout QR kód</a></p>
        </div>
    <?php endif; ?>

    <!-- Owner actions -->
    <?php if ($isOwner): ?>
        <div class="bike-actions">
            <a href="/bike/<?= $bike->getId() ?>/edit" class="btn">Upravit</a>

            <?php if ($bike->isActive()): ?>
                <a href="/theft/report/<?= $bike->getId() ?>" class="btn btn-danger">Nahlásit krádež</a>
            <?php endif; ?>

            <form method="POST" action="/bike/<?= $bike->getId() ?>/delete"
                  onsubmit="return confirm('Opravdu chcete smazat toto kolo?')">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="btn btn-danger">Smazat</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Report found bike (for non-owners) -->
    <?php if (!$isOwner && $bike->isStolen()): ?>
        <div class="bike-actions">
            <a href="/found/report/<?= e($bike->getQrHash()) ?>" class="btn btn-success">Nahlásit nález tohoto kola</a>
        </div>
    <?php endif; ?>
</div>