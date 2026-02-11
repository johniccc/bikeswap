<h1>Registrovat nové kolo</h1>

<?php if (isset($session) && $session->hasFlash('error')): ?>
    <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
<?php endif; ?>

<form method="POST" action="/bike/new" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <fieldset>
        <legend>Základní údaje</legend>

        <div class="form-group">
            <label for="brand">Značka *</label>
            <input type="text" id="brand" name="brand" required maxlength="100"
                   placeholder="např. Trek, Giant, Specialized">
        </div>

        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" id="model" name="model" maxlength="100"
                   placeholder="např. Marlin 7, Defy Advanced">
        </div>

        <div class="form-group">
            <label for="color">Barva *</label>
            <input type="text" id="color" name="color" required maxlength="50"
                   placeholder="např. černá, červeno-bílá">
        </div>

        <div class="form-group">
            <label for="frame_number">Číslo rámu</label>
            <input type="text" id="frame_number" name="frame_number" maxlength="100"
                   placeholder="Najdete na spodní straně rámu">
        </div>

        <div class="form-group">
            <label for="year_of_manufacture">Rok výroby</label>
            <input type="number" id="year_of_manufacture" name="year_of_manufacture"
                   min="1950" max="<?= date('Y') ?>">
        </div>

        <div class="form-group">
            <label for="description">Popis</label>
            <textarea id="description" name="description" rows="4"
                      placeholder="Doplňující informace o kole (příslušenství, úpravy, zvláštní znaky...)"></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Fotografie</legend>

        <div class="form-group">
            <label for="photos">Fotografie kola (max 5 MB / soubor)</label>
            <input type="file" id="photos" name="photos[]" multiple
                   accept="image/jpeg,image/png,image/webp">
            <small>Povolené formáty: JPG, PNG, WebP. První fotka bude nastavena jako hlavní.</small>
        </div>
    </fieldset>

    <fieldset>
        <legend>Sdílení</legend>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_shared" value="1">
                Nabídnout kolo k výpůjčce ostatním uživatelům
            </label>
        </div>
    </fieldset>

    <button type="submit">Zaregistrovat kolo</button>
</form>