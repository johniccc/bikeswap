<div class="auth-page">
  <div class="card max-w-sm" style="margin: 2rem auto;">
    <div class="card-header" style="text-align:center;">
      <h2 style="display:flex;align-items:center;justify-content:center;gap:0.5rem;">
        <i data-lucide="shield-check"></i> Dvoufaktorové ověření
      </h2>
    </div>
    <div class="card-body">
      <form method="POST" action="/login/2fa" id="2fa-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="use_recovery" value="0" id="use-recovery-input">

        <div id="totp-section">
          <p class="text-muted text-sm mb-md" style="text-align:center;">
            Zadejte 6místný kód z vaší ověřovací aplikace.
          </p>

          <div class="form-group">
            <input type="text" name="code" id="2fa-code"
                   inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9]{6}" maxlength="6" autofocus
                   placeholder="000000"
                   style="text-align:center;font-family:monospace;font-size:1.5rem;letter-spacing:0.5rem;padding:0.75rem;">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" style="width:100%;">
              <i data-lucide="check"></i> Ověřit
            </button>
          </div>

          <div style="text-align:center;margin-top:1rem;">
            <a href="#" id="toggle-recovery" class="text-sm">Použít záložní kód</a>
          </div>
        </div>

        <div id="recovery-section" style="display:none;">
          <p class="text-muted text-sm mb-md" style="text-align:center;">
            Zadejte jeden z vašich záložních kódů.
          </p>

          <div class="form-group">
            <input type="text" id="recovery-code"
                   autocomplete="off" maxlength="9"
                   placeholder="xxxx-xxxx"
                   style="text-align:center;font-family:monospace;font-size:1.25rem;letter-spacing:0.2rem;padding:0.75rem;">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" style="width:100%;">
              <i data-lucide="check"></i> Ověřit
            </button>
          </div>

          <div style="text-align:center;margin-top:1rem;">
            <a href="#" id="toggle-totp" class="text-sm">Použít ověřovací aplikaci</a>
          </div>
        </div>
      </form>

      <hr style="margin:1rem 0;">

      <div style="text-align:center;">
        <a href="/login/2fa/cancel" class="text-sm text-muted" style="display:inline-flex;align-items:center;gap:0.35rem;">
          <i data-lucide="arrow-left" style="width:14px;height:14px;flex-shrink:0;"></i> Zpět na přihlášení
        </a>
      </div>

      <p class="text-muted text-sm" style="text-align:center;margin-top:0.75rem;display:flex;align-items:center;justify-content:center;gap:0.35rem;">
        <i data-lucide="clock" style="width:12px;height:12px;flex-shrink:0;"></i> Kód vyprší za 5 minut
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
