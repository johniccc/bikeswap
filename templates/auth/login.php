<div class="main-public-padded">
  <div class="text-center" style="padding:4rem 0">
    <p class="text-muted">Načítám přihlášení...</p>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.BikeSwap && window.BikeSwap.openAuthModal) {
      window.BikeSwap.openAuthModal('login');
    }
  });
</script>
