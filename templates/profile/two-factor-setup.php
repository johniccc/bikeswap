<div class="page-header">
  <h1>Aktivace dvoufaktorového ověření</h1>
  <div class="page-header-actions">
    <a href="/profile/settings" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na nastavení
    </a>
  </div>
</div>

<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3>1. Naskenujte QR kód</h3>
  </div>
  <div class="card-body">
    <p class="text-muted text-sm mb-md">
      Otevřete aplikaci Google Authenticator, Authy nebo jinou TOTP aplikaci a naskenujte tento QR kód.
    </p>

    <div style="text-align:center;margin-bottom:1rem;">
      <img src="<?= e($qrDataUri) ?>" alt="QR kód pro 2FA"
           style="width:200px;height:200px;border-radius:8px;image-rendering:pixelated;">
    </div>

    <p class="text-muted text-sm" style="text-align:center;margin-bottom:0.5rem;">
      Nebo zadejte kód ručně:
    </p>
    <div class="alert alert-info" style="text-align:center;font-family:monospace;font-size:0.95rem;letter-spacing:0.1rem;word-break:break-all;user-select:all;">
      <?= e($secret) ?>
    </div>
  </div>
</div>

<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3>2. Ověřte nastavení</h3>
  </div>
  <div class="card-body">
    <p class="text-muted text-sm mb-md">
      Zadejte 6místný kód z vaší ověřovací aplikace pro potvrzení.
    </p>
    <form method="POST" action="/profile/2fa/enable">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="setup-code">Ověřovací kód</label>
        <input type="text" id="setup-code" name="code"
               inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9]{6}" maxlength="6" required autofocus
               placeholder="000000"
               style="text-align:center;font-family:monospace;font-size:1.25rem;letter-spacing:0.4rem;max-width:200px;">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="shield-check"></i> Aktivovat dvoufaktorové ověření
        </button>
      </div>
    </form>
  </div>
</div>
