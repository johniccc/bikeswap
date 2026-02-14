<div class="notifications-page">
    <div class="notifications-header">
        <h1>Oznámení</h1>

        <?php
            $unreadCount = 0;
            foreach ($notifications as $n) {
                if (!$n->isRead()) $unreadCount++;
            }
        ?>

        <?php if ($unreadCount > 0): ?>
            <form method="POST" action="/notifications/read-all" class="notifications-mark-all">
                <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                <button type="submit" class="btn btn-small">Označit vše jako přečtené</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (isset($session) && $session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
        <p class="notifications-empty">Zatím nemáte žádná oznámení.</p>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= $notification->isRead() ? 'notification-read' : 'notification-unread' ?>">
                    <div class="notification-icon <?= e($notification->getIconClass()) ?>"></div>

                    <div class="notification-content">
                        <div class="notification-title"><?= e($notification->getTitle()) ?></div>
                        <div class="notification-message"><?= e($notification->getMessage()) ?></div>
                        <div class="notification-time"><?= e($notification->getFormattedTime()) ?></div>
                    </div>

                    <div class="notification-actions">
                        <?php if ($notification->getLink()): ?>
                            <?php if (!$notification->isRead()): ?>
                                <!-- Click goes through mark-as-read, then redirects to link -->
                                <form method="POST" action="/notifications/<?= $notification->getId() ?>/read">
                                    <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                                    <button type="submit" class="btn btn-small">Zobrazit</button>
                                </form>
                            <?php else: ?>
                                <a href="<?= e($notification->getLink()) ?>" class="btn btn-small">Zobrazit</a>
                            <?php endif; ?>
                        <?php elseif (!$notification->isRead()): ?>
                            <form method="POST" action="/notifications/<?= $notification->getId() ?>/read">
                                <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                                <button type="submit" class="btn btn-small">Přečteno</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>