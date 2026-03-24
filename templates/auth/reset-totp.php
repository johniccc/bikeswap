<div class="auth-page">
  <div class="card max-w-sm mx-auto mt-xl mb-xl">
    <div class="card-header text-center">
      <h2 class="flex items-center justify-center gap-sm">
        <i data-lucide="shield-check"></i> Ověření přes TOTP
      </h2>
    </div>
    <div class="card-body">
      <p class="text-muted text-sm mb-md text-center">
        Zadejte 6místný kód z vaší ověřovací aplikace.
      </p>

      <form method="POST" action="/forgot-password/totp">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="form-group">
          <input type="text" name="code" id="totp-code"
                 inputmode="numeric" autocomplete="one-time-code"
                 pattern="[0-9]{6}" maxlength="6" autofocus
                 placeholder="000000"
                 class="totp-input">
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary w-full">
            <i data-lucide="check"></i> Ověřit
          </button>
        </div>
      </form>

      <hr class="divider">

      <div class="text-center">
        <a href="/forgot-password/methods" class="text-sm text-muted inline-flex items-center gap-xs">
          <i data-lucide="arrow-left" style="width:14px;height:14px" class="flex-shrink-0"></i> Zpět na výběr metody
        </a>
      </div>

      <p class="text-muted text-sm text-center mt-md flex items-center justify-center gap-xs">
        <i data-lucide="clock" style="width:12px;height:12px" class="flex-shrink-0"></i> Relace vyprší za 10 minut
      </p>
    </div>
  </div>
</div>
