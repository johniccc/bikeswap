<div class="conversation">
    <h1>
        <?php if ($viewerType === 'owner'): ?>
            Konverzace s nálezcem
        <?php else: ?>
            Konverzace s majitelem kola
        <?php endif; ?>
    </h1>

    <?php if (isset($session) && $session->hasFlash('error')): ?>
        <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
    <?php endif; ?>

    <?php if (isset($session) && $session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
    <?php endif; ?>

    <!-- Report & bike info header -->
    <div class="conversation-header">
        <?php if ($bike): ?>
            <div class="conversation-bike">
                <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
                <?php if ($primaryPhoto): ?>
                    <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>" class="conversation-bike-photo">
                <?php endif; ?>
                <div class="conversation-bike-info">
                    <strong><?= e($bike->getFullName()) ?></strong>
                    <span class="bike-color"><?= e($bike->getColor()) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="conversation-meta">
            <span class="status-badge <?= e($report->getStatusClass()) ?>">
                <?= e($report->getStatusLabel()) ?>
            </span>

            <?php if ($report->getFoundLocationText()): ?>
                <span class="meta-item">Místo nálezu: <?= e($report->getFoundLocationText()) ?></span>
            <?php endif; ?>

            <?php if ($report->getFoundDate()): ?>
                <span class="meta-item">Datum: <?= e($report->getFormattedFoundDate()) ?></span>
            <?php endif; ?>

            <?php if ($viewerType === 'owner' && isset($finderLabel)): ?>
                <span class="meta-item">Nálezce: <?= e($finderLabel) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messages -->
    <div class="conversation-messages" id="messages">
        <?php if (empty($messages)): ?>
            <p class="conversation-empty">Zatím žádné zprávy.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php
                $senderType = $msg->getSenderType();
                $isMine = isset($viewerType) && $senderType === $viewerType;
                ?>
                <div class="message <?= e($msg->getSenderClass()) ?> <?= $isMine ? 'mine' : '' ?>">
                    <div class="message-bubble"><?= nl2br(e($msg->getMessage())) ?></div>
                    <?php if (!$msg->isSystemMessage()): ?>
                    <div class="message-meta"><?= e($msg->getSenderLabel()) ?> · <?= e($msg->getFormattedTime()) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Message form (only if report is not resolved) -->
    <?php if (!$report->isResolved()): ?>
        <div class="conversation-compose">
            <?php if ($viewerType === 'finder'): ?>
                <form method="POST" action="/found/conversation/<?= e($report->getConversationToken()) ?>/message">
            <?php else: ?>
                <form method="POST" action="/found/<?= $report->getId() ?>/message">
            <?php endif; ?>
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

                <div class="compose-row">
                    <textarea name="message" rows="3" required
                              placeholder="Napište zprávu..." class="compose-input"></textarea>
                    <button type="submit" class="btn btn-primary compose-send">Odeslat</button>
                </div>
            </form>
        </div>

        <!-- Owner-only: Resolve button -->
        <?php if ($viewerType === 'owner'): ?>
            <div class="conversation-actions">
                <form method="POST" action="/found/<?= $report->getId() ?>/resolve"
                      onsubmit="return confirm('Opravdu jste kolo získali zpět? Tím se uzavře tato konverzace a kolo bude označeno jako aktivní.')">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-success">
                        Kolo jsem získal zpět — uzavřít
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            Tato konverzace je uzavřena — kolo bylo úspěšně navráceno majiteli.
        </div>
    <?php endif; ?>

    <!-- Navigation links -->
    <div class="conversation-nav">
        <?php if ($viewerType === 'owner' && $bike): ?>
            <a href="/bike/<?= e($bike->getQrHash()) ?>">← Zpět na detail kola</a>
            <a href="/dashboard">Moje kola</a>
        <?php elseif ($viewerType === 'finder'): ?>
            <p class="conversation-token-notice">
                Uložte si odkaz na tuto stránku — je to váš jediný přístup ke konverzaci.
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
var messagesDiv = document.getElementById('messages');
if (messagesDiv) {
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
</script>