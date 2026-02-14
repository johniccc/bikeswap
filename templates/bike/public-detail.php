<div class="bike-detail">
    <?php if (isset($session) && $session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
    <?php endif; ?>

    <?php if (isset($session) && $session->hasFlash('error')): ?>
        <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
    <?php endif; ?>

    <!-- Photo gallery -->
    <div class="bike-gallery">
        <?php $photos = $bike->getPhotos(); ?>
        <?php if (!empty($photos)): ?>
            <?php foreach ($photos as $photo): ?>
                <img src="<?= e($photo->getUrl()) ?>"
                     alt="<?= e($bike->getFullName()) ?>"
                     class="bike-gallery-photo <?= $photo->isPrimary() ? 'photo-primary' : '' ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <div class="bike-gallery-placeholder">Žádné fotografie</div>
        <?php endif; ?>
    </div>

    <!-- Bike info -->
    <div class="bike-info">
        <h1><?= e($bike->getFullName()) ?></h1>

        <span class="status-badge status-<?= e($bike->getStatus()) ?>">
            <?php
                $statusLabels = [
                    'active' => 'Aktivní',
                    'stolen' => 'Odcizené',
                    'shared' => 'Sdílené',
                    'inactive' => 'Neaktivní',
                ];
                echo e($statusLabels[$bike->getStatus()] ?? $bike->getStatus());
            ?>
        </span>

        <dl class="bike-specs">
            <dt>Značka</dt>
            <dd><?= e($bike->getBrand()) ?></dd>

            <?php if ($bike->getModel()): ?>
                <dt>Model</dt>
                <dd><?= e($bike->getModel()) ?></dd>
            <?php endif; ?>

            <dt>Barva</dt>
            <dd><?= e($bike->getColor()) ?></dd>

            <?php if ($bike->getYearOfManufacture()): ?>
                <dt>Rok výroby</dt>
                <dd><?= e((string) $bike->getYearOfManufacture()) ?></dd>
            <?php endif; ?>

            <?php if ($bike->getFrameNumber() && $isOwner): ?>
                <dt>Číslo rámu</dt>
                <dd><?= e($bike->getFrameNumber()) ?></dd>
            <?php endif; ?>
        </dl>

        <?php if ($bike->getDescription()): ?>
            <div class="bike-description">
                <h2>Popis</h2>
                <p><?= nl2br(e($bike->getDescription())) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stolen bike alert + report found CTA -->
    <?php if ($bike->isStolen()): ?>
        <div class="alert alert-stolen">
            <h2>Toto kolo je hlášeno jako odcizené!</h2>

            <?php if ($theftReport): ?>
                <p>
                    Datum krádeže: <?= e($theftReport->getFormattedTheftDate()) ?>
                    <?php if ($theftReport->getTheftLocationText()): ?>
                        — <?= e($theftReport->getTheftLocationText()) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if (!$isOwner): ?>
                <p>
                    Poznáváte toto kolo nebo jste ho našli? Pomozte majiteli tím, že nález nahlásíte.
                    Vaše kontaktní údaje nebudou zveřejněny.
                </p>
                <a href="/found/report/<?= e($bike->getQrHash()) ?>" class="btn btn-success btn-large">
                    Nahlásit nález tohoto kola
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Owner actions -->
    <?php if ($isOwner): ?>
        <div class="bike-owner-actions">
            <h2>Správa kola</h2>
            <div class="action-buttons">
                <a href="/bike/<?= $bike->getId() ?>/edit" class="btn">Upravit</a>

                <?php if (!$bike->isStolen()): ?>
                    <a href="/theft/report/<?= $bike->getId() ?>" class="btn btn-danger">Nahlásit krádež</a>
                <?php else: ?>
                    <?php if ($theftReport): ?>
                        <form method="POST" action="/theft/<?= $theftReport->getId() ?>/resolve"
                              onsubmit="return confirm('Opravdu chcete zrušit hlášení krádeže?')">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="btn btn-success">Kolo jsem získal zpět</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Found reports for owner -->
        <?php if (!empty($foundReports)): ?>
            <div class="found-reports-section">
                <h2>Nahlášené nálezy (<?= count($foundReports) ?>)</h2>

                <div class="found-reports-list">
                    <?php foreach ($foundReports as $fr): ?>
                        <div class="found-report-card">
                            <div class="found-report-card-header">
                                <span class="status-badge <?= e($fr->getStatusClass()) ?>">
                                    <?= e($fr->getStatusLabel()) ?>
                                </span>
                                <span class="found-report-date"><?= e($fr->getFormattedFoundDate()) ?></span>
                            </div>

                            <?php if ($fr->getFoundLocationText()): ?>
                                <p class="found-report-location">
                                    Místo: <?= e($fr->getFoundLocationText()) ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($fr->getDescription()): ?>
                                <p class="found-report-desc"><?= e(mb_strimwidth($fr->getDescription(), 0, 120, '…')) ?></p>
                            <?php endif; ?>

                            <a href="/found/<?= $fr->getId() ?>/conversation" class="btn btn-small btn-primary">
                                Otevřít konverzaci
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- QR code (owner only) -->
    <?php if ($isOwner && isset($qrDataUri)): ?>
        <div class="bike-qr">
            <h2>QR kód kola</h2>
            <img src="<?= $qrDataUri ?>" alt="QR kód" class="qr-code-image">
            <p class="qr-hint">Vytiskněte a umístěte na kolo. Po naskenování zobrazí detail kola.</p>
        </div>
    <?php endif; ?>
</div>