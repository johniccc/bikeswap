<h1>Upravit kolo – <?= e($bike->getFullName()) ?></h1>

<?php if (isset($session) && $session->hasFlash('error')): ?>
    <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
<?php endif; ?>

<form method="POST" action="/bike/<?= $bike->getId() ?>/edit" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <fieldset>
        <legend>Základní údaje</legend>

        <div class="form-group">
            <label for="brand">Značka *</label>
            <input type="text" id="brand" name="brand" required maxlength="100"
                   value="<?= e($bike->getBrand()) ?>">
        </div>

        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" id="model" name="model" maxlength="100"
                   value="<?= e($bike->getModel() ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="color">Barva *</label>
            <input type="text" id="color" name="color" required maxlength="50"
                   value="<?= e($bike->getColor()) ?>">
        </div>

        <div class="form-group">
            <label for="frame_number">Číslo rámu</label>
            <input type="text" id="frame_number" name="frame_number" maxlength="100"
                   value="<?= e($bike->getFrameNumber() ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="year_of_manufacture">Rok výroby</label>
            <input type="number" id="year_of_manufacture" name="year_of_manufacture"
                   min="1950" max="<?= date('Y') ?>"
                   value="<?= $bike->getYearOfManufacture() ?>">
        </div>

        <div class="form-group">
            <label for="description">Popis</label>
            <textarea id="description" name="description" rows="4"><?= e($bike->getDescription() ?? '') ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Současné fotografie</legend>

        <?php if (!empty($bike->getPhotos())): ?>
            <div class="photo-grid">
                <?php foreach ($bike->getPhotos() as $photo): ?>
                    <div class="photo-item">
                        <img src="<?= e($photo->getUrl()) ?>" alt="Foto kola">

                        <?php if ($photo->isPrimary()): ?>
                            <span class="badge badge-primary">Hlavní</span>
                        <?php else: ?>
                            <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/primary" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                <button type="submit" class="btn btn-small">Nastavit jako hlavní</button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/delete"
                              style="display:inline;"
                              onsubmit="return confirm('Opravdu smazat tuto fotku?')">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Smazat</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Žádné fotografie</p>
        <?php endif; ?>

        <div class="form-group">
            <label for="photos">Přidat další fotografie</label>
            <input type="file" id="photos" name="photos[]" multiple
                   accept="image/jpeg,image/png,image/webp">
        </div>
    </fieldset>

    <fieldset>
        <legend>Sdílení</legend>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_shared" value="1"
                    <?= $bike->isShared() ? 'checked' : '' ?>>
                Nabídnout kolo k výpůjčce
            </label>
        </div>
    </fieldset>

    <button type="submit">Uložit změny</button>
    <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn">Zrušit</a>
</form>