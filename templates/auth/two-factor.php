<div class="auth-page">
  <div class="card max-w-sm mx-auto mt-xl mb-xl">
    <div class="card-header text-center">
      <h2 class="flex items-center justify-center gap-sm">
        <i data-lucide="shield-check"></i> Dvoufaktorové ověření
      </h2>
    </div>
    <div class="card-body">
      <form method="POST" action="/login/2fa" id="2fa-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="use_recovery" value="0" id="use-recovery-input">

        <div id="totp-section">
          <p class="text-muted text-sm mb-md text-center">
            Zadejte 6místný kód z vaší ověřovací aplikace.
          </p>

          <div class="form-group">
            <input type="text" name="code" id="2fa-code"
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

          <div class="text-center mt-md">
            <a href="#" id="toggle-recovery" class="text-sm">Použít záložní kód</a>
          </div>
        </div>

        <div id="recovery-section" style="display:none;">
          <p class="text-muted text-sm mb-md text-center">
            Zadejte jeden z vašich záložních kódů.
          </p>

          <div class="form-group">
            <input type="text" id="recovery-code"
                   autocomplete="off" maxlength="9"
                   placeholder="xxxx-xxxx"
                   class="recovery-input">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary w-full">
              <i data-lucide="check"></i> Ověřit
            </button>
          </div>

          <div class="text-center mt-md">
            <a href="#" id="toggle-totp" class="text-sm">Použít ověřovací aplikaci</a>
          </div>
        </div>
      </form>

      <hr class="divider">

      <div class="text-center">
        <a href="/login/2fa/cancel" class="text-sm text-muted inline-flex items-center gap-xs">
          <i data-lucide="arrow-left" style="width:14px;height:14px" class="flex-shrink-0"></i> Zpět na přihlášení
        </a>
      </div>

      <p class="text-muted text-sm text-center mt-md flex items-center justify-center gap-xs">
        <i data-lucide="clock" style="width:12px;height:12px" class="flex-shrink-0"></i> Kód vyprší za 5 minut
      </p>
    </div>
  </div>
</div>

<script>
(function() {
  var totpSection = document.getElementById('totp-section');
  var recoverySection = document.getElementById('recovery-section');
  var useRecoveryInput = document.getElementById('use-recovery-input');
  var totpCode = document.getElementById('2fa-code');
  var recoveryCode = document.getElementById('recovery-code');

  document.getElementById('toggle-recovery').addEventListener('click', function(e) {
    e.preventDefault();
    totpSection.style.display = 'none';
    recoverySection.style.display = 'block';
    useRecoveryInput.value = '1';
    totpCode.removeAttribute('name');
    recoveryCode.setAttribute('name', 'code');
    recoveryCode.focus();
  });

  document.getElementById('toggle-totp').addEventListener('click', function(e) {
    e.preventDefault();
    recoverySection.style.display = 'none';
    totpSection.style.display = 'block';
    useRecoveryInput.value = '0';
    recoveryCode.removeAttribute('name');
    totpCode.setAttribute('name', 'code');
    totpCode.focus();
  });
}());
</script>
