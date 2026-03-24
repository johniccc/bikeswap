<div class="auth-page">
  <div class="card max-w-sm mx-auto mt-xl mb-xl">
    <div class="card-header text-center">
      <h2 class="flex items-center justify-center gap-sm">
        <i data-lucide="key"></i> Záložní kód
      </h2>
    </div>
    <div class="card-body">
      <p class="text-muted text-sm mb-md text-center">
        Zadejte jeden z vašich záložních kódů.
      </p>

      <form method="POST" action="/forgot-password/recovery">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div class="form-group">
          <input type="text" name="code" id="recovery-code"
                 autocomplete="off" maxlength="9" autofocus
                 placeholder="xxxx-xxxx"
                 class="recovery-input">
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
