<div class="error-page">
  <div class="error-page-icon">
    <i data-lucide="ban"></i>
  </div>
  <h1>405</h1>
  <p class="error-page-title">Metoda neni povolena</p>
  <p class="text-muted"><?= e($message ?? 'Tato metoda neni pro tento endpoint povolena.') ?></p>
  <a href="/" class="btn btn-primary mt-lg">
    <i data-lucide="home"></i> Zpet na hlavni stranku
  </a>
</div>
