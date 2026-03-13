<div class="page-header">
  <h1>Nové upozornění na kolo</h1>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="/admin/warnings/new">
      <input type="hidden" name="_csrf" value="<?= $session->csrfToken() ?>">

      <div class="form-group">
        <label for="bike_id">ID kola *</label>
        <input type="number" id="bike_id" name="bike_id" class="form-control" value="<?= e(old('bike_id') ?? $bikeId ?? '') ?>" required min="1">
        <?php if (isset($bike) && $bike): ?>
          <small class="text-muted">Kolo: <?= e($bike->getFullName()) ?> (vlastník ID: <?= $bike->getOwnerId() ?>)</small>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="deadline">Termín vyzvednutí *</label>
        <input type="date" id="deadline" name="deadline" class="form-control" value="<?= e(old('deadline') ?? '') ?>" required min="<?= date('Y-m-d') ?>">
      </div>

      <div class="form-group">
        <label for="location">Místo nálezu</label>
        <input type="text" id="location" name="location" class="form-control" value="<?= e(old('location') ?? '') ?>" placeholder="Pardubice hlavní nádraží">
      </div>

      <div class="form-group">
        <label for="reason">Důvod upozornění *</label>
        <textarea id="reason" name="reason" class="form-control" rows="4" required><?= e(old('reason') ?? '') ?></textarea>
      </div>

      <div class="form-actions">
        <a href="/admin/warnings" class="btn btn-secondary">Zpět</a>
        <button type="submit" class="btn btn-primary">Vytvořit upozornění</button>
      </div>
    </form>
  </div>
</div>
