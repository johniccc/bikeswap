<div class="profile-page">
    <h1>Profil: <?= e($user->getName()) ?></h1>

    <div class="card mb-2">
        <div class="card-body">
            <h2>Karma</h2>
            <p>
                Skóre: <strong><?= e((string)$user->getKarmaScore()) ?></strong>
                &nbsp;·&nbsp;
                Úroveň: <strong><?= e($user->getKarmaLevel()) ?></strong>
            </p>
            <a href="/profile/settings" class="btn btn-secondary btn-sm">Nastavení profilu</a>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h2>Moje kola</h2>
            <?php if (empty($bikes)): ?>
                <p class="text-muted">Nemáte žádná registrovaná kola.</p>
                <a href="/bike/new" class="btn btn-primary btn-sm">Přidat kolo</a>
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
                                <h3 class="bike-card-title">
                                    <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
                                </h3>
                                <div class="bike-card-actions">
                                    <a href="/bike/<?= e((string)$bike->getId()) ?>/edit" class="btn btn-sm">Upravit</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h2>Moje rezervace</h2>
            <?php if (empty($reservations)): ?>
                <p class="text-muted">Nemáte žádné rezervace jako výpůjčitel.</p>
            <?php else: ?>
                <ul class="list-plain">
                    <?php foreach ($reservations as $r): ?>
                        <li>
                            <a href="/reservation/<?= e((string)$r->getId()) ?>">
                                Rezervace #<?= e((string)$r->getId()) ?>
                            </a>
                            — <span class="status-badge <?= e($r->getStatusClass()) ?>"><?= e($r->getStatusLabel()) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($foundReports)): ?>
    <div class="card mb-2">
        <div class="card-body">
            <h2>Moje nálezy</h2>
            <ul class="list-plain">
                <?php foreach ($foundReports as $report): ?>
                    <li>
                        Nález #<?= e((string)$report->getId()) ?>
                        — <span class="status-badge <?= e($report->getStatusClass()) ?>"><?= e($report->getStatusLabel()) ?></span>
                        (<?= e($report->getFormattedFoundDate()) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>
