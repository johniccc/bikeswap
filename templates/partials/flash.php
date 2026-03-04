<?php if (isset($session)): ?>
  <?php if ($flashSuccess = $session->getFlash('success')): ?>
    <div class="alert alert-success"><i data-lucide="check-circle"></i> <?= e($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashError = $session->getFlash('error')): ?>
    <div class="alert alert-error"><i data-lucide="alert-circle"></i> <?= e($flashError) ?></div>
  <?php endif; ?>
  <?php if ($flashInfo = $session->getFlash('info')): ?>
    <div class="alert alert-info"><i data-lucide="info"></i> <?= e($flashInfo) ?></div>
  <?php endif; ?>
  <?php if ($flashWarning = $session->getFlash('warning')): ?>
    <div class="alert alert-warning"><i data-lucide="alert-triangle"></i> <?= e($flashWarning) ?></div>
  <?php endif; ?>
<?php endif; ?>
