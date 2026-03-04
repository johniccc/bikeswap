<h1>Sdílená kola k výpůjčce</h1>

<?php if (empty($bikes)): ?>
    <div class="alert alert-info">
        Momentálně žádná kola nejsou nabízena k výpůjčce.
    </div>
<?php else: ?>
    <div class="bike-grid">
        <?php foreach ($bikes as $bike): ?>
            <div class="card bike-card">
                <?php $photo = $bike->getPrimaryPhoto(); ?>
                <?php if ($photo): ?>
                    <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-image">
                <?php else: ?>
                    <div class="bike-card-image bike-card-no-image">Bez fotografie</div>
                <?php endif; ?>

                <div class="bike-card-body">
                    <h3><?= e($bike->getFullName()) ?></h3>

                    <div class="bike-card-meta">
                        <span>Barva: <?= e($bike->getColor()) ?></span>
                        <?php if ($bike->getYearOfManufacture()): ?>
                            <span>Rok: <?= $bike->getYearOfManufacture() ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($bike->getDescription()): ?>
                        <p class="text-muted"><?= e(mb_substr($bike->getDescription(), 0, 120)) ?><?= mb_strlen($bike->getDescription()) > 120 ? '…' : '' ?></p>
                    <?php endif; ?>

                    <div class="bike-card-actions">
                        <?php
                        $isLoggedIn = isset($currentUser) && $currentUser !== null;
                        $isOwner    = $isLoggedIn && $bike->isOwnedBy($currentUser->getId());
                        ?>
                        <?php if ($isLoggedIn && !$isOwner): ?>
                            <a href="/reservation/new/<?= $bike->getId() ?>" class="btn btn-primary">Rezervovat</a>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="/login?redirect=/shared" class="btn btn-primary">Přihlásit a rezervovat</a>
                        <?php endif; ?>
                        <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn">Detail</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>