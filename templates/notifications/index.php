<?php
$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n->isRead()) $unreadCount++;
}
?>

<div class="page-header">
  <h1>Oznámení</h1>
  <div class="page-header-actions">
    <?php if ($unreadCount > 0): ?>
      <form method="POST" action="/notifications/read-all">
        <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <button type="submit" class="btn btn-ghost btn-sm">
          <i data-lucide="check-check"></i> Označit vše jako přečtené
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($notifications)): ?>
  <div class="empty-state">
    <i data-lucide="bell-off"></i>
    <h3>Žádná oznámení</h3>
    <p>Zatím nemáte žádná oznámení.</p>
  </div>
<?php else: ?>

  <?php if (!empty($actions)): ?>
    <div class="notifications-section">
      <h2 class="notifications-section-title">Akce</h2>
      <div class="notifications-list">
        <?php foreach ($actions as $notification): ?>
          <div class="notification-item notification-action <?= $notification->isRead() ? 'notification-read' : 'notification-unread' ?>">
            <div class="notification-icon">
              <?php if (!$notification->isRead()): ?>
                <span class="notification-dot"></span>
              <?php endif; ?>
              <i data-lucide="x-circle"></i>
            </div>

            <div class="notification-content">
              <div class="notification-title"><?= e($notification->getTitle()) ?></div>
              <div class="notification-message text-muted text-sm"><?= e($notification->getMessage()) ?></div>
              <div class="notification-time text-muted text-sm"><?= e($notification->getFormattedTime()) ?></div>
            </div>

            <div class="notification-actions">
              <?php if ($notification->getLink()): ?>
                <?php if (!$notification->isRead()): ?>
                  <form method="POST" action="/notifications/<?= $notification->getId() ?>/read">
                    <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Zobrazit</button>
                  </form>
                <?php else: ?>
                  <a href="<?= e($notification->getLink()) ?>" class="btn btn-sm btn-ghost">Zobrazit</a>
                <?php endif; ?>
              <?php elseif (!$notification->isRead()): ?>
                <form method="POST" action="/notifications/<?= $notification->getId() ?>/read">
                  <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                  <button type="submit" class="btn btn-sm btn-ghost">
                    <i data-lucide="check"></i>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="notifications-section">
    <?php if (!empty($actions)): ?>
      <h2 class="notifications-section-title">Zprávy</h2>
    <?php endif; ?>
    <?php if (empty($messages)): ?>
      <div class="empty-state">
        <i data-lucide="bell-off"></i>
        <h3>Žádné zprávy</h3>
        <p>Zatím nemáte žádné zprávy.</p>
      </div>
    <?php else: ?>
      <div class="notifications-list">
        <?php foreach ($messages as $notification): ?>
          <div class="notification-item <?= $notification->isRead() ? 'notification-read' : 'notification-unread' ?>">
            <div class="notification-icon">
              <?php if (!$notification->isRead()): ?>
                <span class="notification-dot"></span>
              <?php endif; ?>
              <i data-lucide="bell"></i>
            </div>

            <div class="notification-content">
              <div class="notification-title"><?= e($notification->getTitle()) ?></div>
              <div class="notification-message text-muted text-sm"><?= e($notification->getMessage()) ?></div>
              <div class="notification-time text-muted text-sm"><?= e($notification->getFormattedTime()) ?></div>
            </div>

            <div class="notification-actions">
              <?php if ($notification->getLink()): ?>
                <?php if (!$notification->isRead()): ?>
                  <form method="POST" action="/notifications/<?= $notification->getId() ?>/read">
                    <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Zobrazit</button>
                  </form>
                <?php else: ?>
                  <a href="<?= e($notification->getLink()) ?>" class="btn btn-sm btn-ghost">Zobrazit</a>
                <?php endif; ?>
              <?php elseif (!$notification->isRead()): ?>
                <form method="POST" action="/notifications/<?= $notification->getId() ?>/read">
                  <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                  <button type="submit" class="btn btn-sm btn-ghost">
                    <i data-lucide="check"></i>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>
