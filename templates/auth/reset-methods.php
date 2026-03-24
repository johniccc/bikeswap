<div class="main-public-padded flex justify-center" style="padding-top:4rem">
  <div class="w-full" style="max-width:420px">

    <div class="card">
      <div class="card-header">
        <h2 class="mt-0 mb-0">Způsob ověření</h2>
      </div>
      <div class="card-body">
        <p class="text-muted mb-lg">Vyberte, jak chcete ověřit svou identitu pro obnovení hesla.</p>

        <!-- Email method (always available) -->
        <form method="POST" action="/forgot-password/email" class="mb-md">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="btn btn-primary btn-full flex items-center gap-sm justify-center">
            <i data-lucide="mail"></i> Odeslat odkaz na e-mail
          </button>
        </form>

        <?php if ($has2fa): ?>
          <a href="/forgot-password/totp" class="btn btn-outline btn-full flex items-center gap-sm justify-center mb-md">
            <i data-lucide="shield-check"></i> Ověřit přes autentizační aplikaci
          </a>
        <?php endif; ?>

        <?php if ($hasRecovery): ?>
          <a href="/forgot-password/recovery" class="btn btn-outline btn-full flex items-center gap-sm justify-center mb-md">
            <i data-lucide="key"></i> Použít záložní kód
          </a>
        <?php endif; ?>

        <div class="auth-switch mt-md">
          <a href="/forgot-password" class="text-muted text-sm">← Zpět na zadání e-mailu</a>
        </div>
      </div>
    </div>

  </div>
</div>
