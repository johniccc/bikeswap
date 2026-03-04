<div class="error-page">
  <div class="error-page-icon">
    <i data-lucide="server-crash"></i>
  </div>
  <h1>500</h1>
  <p class="error-page-title">Chyba serveru</p>
  <p class="text-muted"><?= e($message ?? 'Něco se pokazilo. Zkuste to prosím znovu později.') ?></p>
  <a href="/" class="btn btn-primary mt-lg">
    <i data-lucide="home"></i> Zpět na hlavní stránku
  </a>
</div>
