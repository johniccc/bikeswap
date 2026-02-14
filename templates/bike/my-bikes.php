<div class="my-bikes">
    <div class="my-bikes-header">
        <h1>Moje kola</h1>
        <a href="/bike/new" class="btn btn-primary">Registrovat kolo</a>
    </div>

    <?php if (isset($session) && $session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
    <?php endif; ?>

    <?php if (isset($session) && $session->hasFlash('error')): ?>
        <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
    <?php endif; ?>

    <?php if (empty($bikes)): ?>
        <div class="empty-state">
            <p>Zatím nemáte registrované žádné kolo.</p>
            <a href="/bike/new" class="btn btn-primary">Zaregistrovat první kolo</a>
        </div>
    <?php else: ?>
        <div class="bike-grid">
            <?php foreach ($bikes as $bike): ?>
                <div class="bike-card">
                    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="bike-card-link">
                        <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
                        <?php if ($primaryPhoto): ?>
                            <img src="<?= e($primaryPhoto->getUrl()) ?>"
                                 alt="<?= e($bike->getFullName()) ?>"
                                 class="bike-card-photo">
                        <?php else: ?>
                            <div class="bike-card-photo-placeholder">Bez fotografie</div>
                        <?php endif; ?>

                        <div class="bike-card-info">
                            <h2 class="bike-card-name"><?= e($bike->getFullName()) ?></h2>
                            <span class="bike-card-color"><?= e($bike->getColor()) ?></span>

                            <div class="bike-card-badges">
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

                                <?php
                                    $frCount = $foundReportCounts[$bike->getId()] ?? 0;
                                ?>
                                <?php if ($frCount > 0): ?>
                                    <span class="found-report-badge" title="<?= $frCount ?> aktivní<?= $frCount > 1 ? 'ch' : '' ?> nález<?= $frCount > 4 ? 'ů' : ($frCount > 1 ? 'y' : '') ?>">
                                        <?= $frCount ?> nález<?= $frCount > 4 ? 'ů' : ($frCount > 1 ? 'y' : '') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>