<div class="error-page">
  <div class="error-page-icon">
    <i data-lucide="server-crash"></i>
  </div>
  <h1>500</h1>
  <p class="error-page-title">Chyba serveru</p>
  <p class="text-muted"><?= e($message ?? 'Neco se pokazilo. Zkuste to prosim znovu pozdeji.') ?></p>
  <a href="/" class="btn btn-primary mt-lg">
    <i data-lucide="home"></i> Zpet na hlavni stranku
  </a>
</div>
