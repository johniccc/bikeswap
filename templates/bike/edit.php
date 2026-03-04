<h1>Upravit kolo – <?= e($bike->getFullName()) ?></h1>

<?php if (isset($session) && $session->hasFlash('error')): ?>
    <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
<?php endif; ?>
<?php if (isset($session) && $session->hasFlash('success')): ?>
    <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
<?php endif; ?>

<!-- ═══ HLAVNÍ FORMULÁŘ – editace údajů ═══ -->
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
        <legend>Sdílení</legend>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_shared" value="1"
                    <?= $bike->isShared() ? 'checked' : '' ?>>
                Nabídnout kolo k výpůjčce ostatním uživatelům
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Přidat nové fotografie</legend>
        <div class="form-group">
            <label for="photo-input">Vyberte soubory (JPEG, PNG, WebP)</label>
            <input type="file" name="photos[]" id="photo-input" multiple accept="image/*" class="form-control">
            <div class="photo-preview-grid" id="photo-preview"></div>
            <input type="hidden" name="primary_index" id="primary-index" value="0">
            <p class="form-text">První fotografie bude primární (nebo vyberte jinou). Podporované formáty: JPG, PNG, WebP.</p>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Uložit změny</button>
        <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn">Zrušit</a>
    </div>
</form>

<!-- ═══ SPRÁVA FOTOGRAFIÍ ═══ -->
<?php if (!empty($bike->getPhotos())): ?>
<section class="card section-spaced">
    <div class="card-body">
        <h2>Současné fotografie</h2>

        <div class="photo-grid">
            <?php foreach ($bike->getPhotos() as $photo): ?>
                <div class="photo-item <?= $photo->isPrimary() ? 'photo-item-primary' : '' ?>">
                    <img src="<?= e($photo->getUrl()) ?>" alt="Foto kola">

                    <div class="photo-item-actions">
                        <?php if ($photo->isPrimary()): ?>
                            <span class="badge badge-success">Hlavní</span>
                        <?php else: ?>
                            <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/primary">
                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                <button type="submit" class="btn btn-small">Nastavit jako hlavní</button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" action="/bike/<?= $bike->getId() ?>/photo/<?= $photo->getId() ?>/delete"
                              onsubmit="return confirm('Opravdu smazat tuto fotku?')">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="btn btn-small btn-danger">Smazat</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ SMAZAT KOLO ═══ -->
<section class="danger-zone">
    <form method="POST" action="/bike/<?= $bike->getId() ?>/delete"
          onsubmit="return confirm('Opravdu chcete trvale smazat toto kolo? Tuto akci nelze vrátit.')">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="btn btn-danger">Smazat kolo</button>
    </form>
</section>