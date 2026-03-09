<?php
// Render the home page content as background, then open the auth modal on top
include __DIR__ . '/../home/index.php';
?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.BikeSwap && window.BikeSwap.openAuthModal) {
      window.BikeSwap.openAuthModal('register');
    }
  });
</script>
