<h1>Moje kola</h1>

<?php if (isset($session) && $session->hasFlash('success')): ?>
    <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
<?php endif; ?>

<p><a href="/bike/new" class="btn">+ Zaregistrovat nové kolo</a></p>

<?php if (empty($bikes)): ?>
    <p>Zatím nemáte zaregistrované žádné kolo. <a href="/bike/new">Zaregistrujte své první kolo</a>.</p>

<?php else: ?>
    <div class="bike-grid">
        <?php foreach ($bikes as $bike): ?>
            <div class="bike-card">
                <!-- Primary photo -->
                <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
                <?php if ($primaryPhoto): ?>
                    <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
                <?php else: ?>
                    <div class="bike-card-photo no-photo">Bez fotky</div>
                <?php endif; ?>

                <!-- Info -->
                <div class="bike-card-info">
                    <h2><a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a></h2>
                    <p class="bike-card-color"><?= e($bike->getColor()) ?></p>
                    <span class="status-badge <?= e($bike->getStatusClass()) ?>">
                        <?= e($bike->getStatusLabel()) ?>
                    </span>

                    <?php if ($bike->isShared()): ?>
                        <span class="badge">Sdílené</span>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="bike-card-actions">
                    <a href="/bike/<?= e($bike->getQrHash()) ?>">Detail</a>
                    <a href="/bike/<?= $bike->getId() ?>/edit">Upravit</a>
                    <a href="/file/qr/<?= e($bike->getQrHash()) ?>" download>QR kód</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>