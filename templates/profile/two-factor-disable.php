<div class="page-header">
  <h1>Deaktivace dvoufaktorového ověření</h1>
  <div class="page-header-actions">
    <a href="/profile/settings" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na nastavení
    </a>
  </div>
</div>

<div class="card max-w-lg mb-lg">
  <div class="card-body">
    <div class="alert alert-warning mb-md">
      <i data-lucide="alert-triangle"></i>
      <div>
        <strong>Po deaktivaci bude váš účet chráněn pouze heslem.</strong>
      </div>
    </div>

    <form method="POST" action="/profile/2fa/disable">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="disable-password">Heslo</label>
        <input type="password" id="disable-password" name="password" required autocomplete="current-password">
      </div>

      <div class="form-group">
        <label for="disable-code">Ověřovací kód z aplikace</label>
        <input type="text" id="disable-code" name="code"
               inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9]{6}" maxlength="6" required
               placeholder="000000"
               class="totp-input">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-danger">
          <i data-lucide="shield-off"></i> Deaktivovat 2FA
        </button>
        <a href="/profile/settings" class="btn btn-ghost">Zrušit</a>
      </div>
    </form>
  </div>
</div>
