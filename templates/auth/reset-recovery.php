<div class="auth-page">
  <div class="card max-w-sm" style="margin: 2rem auto;">
    <div class="card-header" style="text-align:center;">
      <h2 style="display:flex;align-items:center;justify-content:center;gap:0.5rem;">
        <i data-lucide="key"></i> Záložní kód
      </h2>
    </div>
    <div class="card-body">
      <p class="text-muted text-sm mb-md" style="text-align:center;">
        Zadejte jeden z vašich záložních kódů.
      </p>

      <form method="POST" action="/forgot-password/recovery">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="form-group">
          <input type="text" name="code" id="recovery-code"
                 autocomplete="off" maxlength="9" autofocus
                 placeholder="xxxx-xxxx"
                 style="text-align:center;font-family:monospace;font-size:1.25rem;letter-spacing:0.2rem;padding:0.75rem;">
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" style="width:100%;">
            <i data-lucide="check"></i> Ověřit
          </button>
        </div>
      </form>

      <hr style="margin:1rem 0;">

      <div style="text-align:center;">
        <a href="/forgot-password/methods" class="text-sm text-muted" style="display:inline-flex;align-items:center;gap:0.35rem;">
          <i data-lucide="arrow-left" style="width:14px;height:14px;flex-shrink:0;"></i> Zpět na výběr metody
        </a>
      </div>

      <p class="text-muted text-sm" style="text-align:center;margin-top:0.75rem;display:flex;align-items:center;justify-content:center;gap:0.35rem;">
        <i data-lucide="clock" style="width:12px;height:12px;flex-shrink:0;"></i> Relace vyprší za 10 minut
      </p>
    </div>
  </div>
</div>
