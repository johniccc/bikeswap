<div class="main-public-padded flex justify-center" style="padding-top:4rem">
  <div class="w-full max-w-sm">

    <div class="card">
      <div class="card-header">
        <h2 class="mb-0">Zapomenuté heslo</h2>
      </div>
      <div class="card-body">
        <p class="text-muted mb-lg">Zadejte svůj e-mail a pošleme vám odkaz pro obnovení hesla.</p>

        <form method="POST" action="/forgot-password">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

          <div class="form-group">
            <label for="fp-email">E-mail</label>
            <input type="email" id="fp-email" name="email" required autocomplete="email" placeholder="vas@email.cz">
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            <i data-lucide="send"></i> Odeslat odkaz pro obnovení
          </button>
        </form>

        <div class="auth-switch mt-md">
          <a href="/login" style="color:var(--text-muted);font-size:0.875rem">← Zpět na přihlášení</a>
        </div>
      </div>
    </div>

  </div>
</div>
