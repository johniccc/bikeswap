<h1>Odcizená kola</h1>

<p>Hledáte své ukradené kolo nebo jste nalezli podezřelé kolo? Prohledejte databázi odcizených kol.</p>

<!-- Search filters -->
<form method="GET" action="/stolen" class="search-form">
    <div class="form-row">
        <div class="form-group">
            <label for="brand">Značka</label>
            <input type="text" id="brand" name="brand"
                   value="<?= e($filters['brand'] ?? '') ?>"
                   placeholder="např. Trek, Giant">
        </div>

        <div class="form-group">
            <label for="color">Barva</label>
            <input type="text" id="color" name="color"
                   value="<?= e($filters['color'] ?? '') ?>"
                   placeholder="např. černá">
        </div>

        <div class="form-group">
            <label for="frame_number">Číslo rámu</label>
            <input type="text" id="frame_number" name="frame_number"
                   value="<?= e($filters['frame_number'] ?? '') ?>"
                   placeholder="Číslo rámu">
        </div>
    </div>

    <button type="submit" class="btn">Hledat</button>

    <?php if (!empty($filters)): ?>
        <a href="/stolen" class="btn">Zrušit filtr</a>
    <?php endif; ?>
</form>

<!-- Results -->
<?php if (empty($bikes)): ?>
    <?php if (!empty($filters)): ?>
        <p>Žádná odcizená kola neodpovídají vašemu hledání.</p>
    <?php else: ?>
        <p>V databázi nejsou žádná odcizená kola.</p>
    <?php endif; ?>

<?php else: ?>
    <p>Nalezeno <strong><?= count($bikes) ?></strong> odcizených kol.</p>

    <div class="bike-grid">
        <?php foreach ($bikes as $bike): ?>
            <div class="bike-card bike-card-stolen">
                <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
                <?php if ($primaryPhoto): ?>
                    <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="bike-card-photo">
                <?php else: ?>
                    <div class="bike-card-photo no-photo">Bez fotky</div>
                <?php endif; ?>

                <div class="bike-card-info">
                    <h2><a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a></h2>
                    <p class="bike-card-color"><?= e($bike->getColor()) ?></p>
                    <?php if ($bike->getFrameNumber()): ?>
                        <p class="bike-card-frame">Rám: <?= e($bike->getFrameNumber()) ?></p>
                    <?php endif; ?>
                    <span class="status-badge status-stolen">Odcizené</span>
                </div>

                <div class="bike-card-actions">
                    <a href="/bike/<?= e($bike->getQrHash()) ?>">Detail</a>
                    <a href="/found/report/<?= e($bike->getQrHash()) ?>">Nahlásit nález</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>