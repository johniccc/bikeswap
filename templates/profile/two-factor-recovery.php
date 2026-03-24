<div class="page-header">
  <h1>Záložní kódy</h1>
</div>

<div class="card max-w-lg mb-lg">
  <div class="card-body">
    <div class="alert alert-warning mb-md">
      <i data-lucide="alert-triangle"></i>
      <div>
        <strong>Uložte si tyto kódy na bezpečné místo.</strong>
        <p class="mt-xs">Každý kód lze použít pouze jednou a po opuštění stránky je již neuvidíte.</p>
      </div>
    </div>

    <div id="recovery-codes-grid" class="grid-2 mb-lg">
      <?php foreach ($codes as $code): ?>
        <div class="text-mono text-center p-sm rounded" style="border:1px solid var(--border);background:var(--bg-secondary);">
          <?= e($code) ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="form-actions">
      <button type="button" class="btn btn-secondary" id="copy-codes-btn">
        <i data-lucide="copy"></i> Kopírovat kódy
      </button>
      <a href="/profile/settings" class="btn btn-primary">
        <i data-lucide="check"></i> Hotovo
      </a>
    </div>
  </div>
</div>

<script>
(function() {
  var btn = document.getElementById('copy-codes-btn');
  if (btn) {
    var originalIcon = btn.querySelector('i');
    var originalText = btn.lastChild;

    btn.addEventListener('click', function() {
      var codes = [];
      document.querySelectorAll('#recovery-codes-grid div').forEach(function(el) {
        codes.push(el.textContent.trim());
      });
      navigator.clipboard.writeText(codes.join('\n')).then(function() {
        var icon = btn.querySelector('i');
        if (icon) icon.setAttribute('data-lucide', 'check');
        btn.lastChild.textContent = ' Zkopírováno!';
        if (window.lucide) lucide.createIcons();
        setTimeout(function() {
          var icon = btn.querySelector('i');
          if (icon) icon.setAttribute('data-lucide', 'copy');
          btn.lastChild.textContent = ' Kopírovat kódy';
          if (window.lucide) lucide.createIcons();
        }, 2000);
      });
    });
  }
}());
</script>
