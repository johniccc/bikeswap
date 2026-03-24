<div class="main-public-padded" style="display:flex;justify-content:center;padding-top:4rem">
  <div style="width:100%;max-width:420px">

    <div class="card">
      <div class="card-header">
        <h2 style="margin:0">Způsob ověření</h2>
      </div>
      <div class="card-body">
        <p class="text-muted mb-lg">Vyberte, jak chcete ověřit svou identitu pro obnovení hesla.</p>

        <!-- Email method (always available) -->
        <form method="POST" action="/forgot-password/email" style="margin-bottom:0.75rem;">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="btn btn-primary btn-full" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
            <i data-lucide="mail"></i> Odeslat odkaz na e-mail
          </button>
        </form>

        <?php if ($has2fa): ?>
          <a href="/forgot-password/totp" class="btn btn-outline btn-full" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;margin-bottom:0.75rem;">
            <i data-lucide="shield-check"></i> Ověřit přes autentizační aplikaci
          </a>
        <?php endif; ?>

        <?php if ($hasRecovery): ?>
          <a href="/forgot-password/recovery" class="btn btn-outline btn-full" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;margin-bottom:0.75rem;">
            <i data-lucide="key"></i> Použít záložní kód
          </a>
        <?php endif; ?>

        <div class="auth-switch mt-md">
          <a href="/forgot-password" style="color:var(--text-muted);font-size:0.875rem">← Zpět na zadání e-mailu</a>
        </div>
      </div>
    </div>

  </div>
</div>
