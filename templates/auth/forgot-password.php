<div class="main-public-padded" style="display:flex;justify-content:center;padding-top:4rem">
  <div style="width:100%;max-width:420px">

    <div class="card">
      <div class="card-header">
        <h2 style="margin:0">Zapomenuté heslo</h2>
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
