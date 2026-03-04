<div class="page-header">
  <h1>
    <?php if ($viewerType === 'owner'): ?>
      Konverzace s nalezcem
    <?php else: ?>
      Konverzace s majitelem kola
    <?php endif; ?>
  </h1>
  <div class="page-header-actions">
    <?php if ($viewerType === 'owner' && $bike): ?>
      <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> Zpet na detail kola
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Report & bike info header -->
<div class="card mb-lg">
  <div class="card-body">
    <div class="flex gap-lg items-center flex-wrap">
      <?php if ($bike): ?>
        <?php $primaryPhoto = $bike->getPrimaryPhoto(); ?>
        <?php if ($primaryPhoto): ?>
          <img src="<?= e($primaryPhoto->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
               style="width:80px;height:60px;object-fit:cover;border-radius:var(--radius-md)">
        <?php endif; ?>
        <div>
          <h3 style="margin-bottom:0.15rem"><?= e($bike->getFullName()) ?></h3>
          <p class="text-muted text-sm">
            <i data-lucide="palette" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
            <?= e($bike->getColor()) ?>
          </p>
        </div>
      <?php endif; ?>

      <div style="margin-left:auto" class="flex gap-sm flex-wrap items-center">
        <span class="status-badge <?= e($report->getStatusClass()) ?>">
          <?= e($report->getStatusLabel()) ?>
        </span>
      </div>
    </div>

    <div class="reservation-detail-grid mt-md">
      <?php if ($report->getFoundLocationText()): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="map-pin" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i> Misto nalezu
          </span>
          <strong><?= e($report->getFoundLocationText()) ?></strong>
        </div>
      <?php endif; ?>

      <?php if ($report->getFoundDate()): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="calendar" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i> Datum nalezu
          </span>
          <strong><?= e($report->getFormattedFoundDate()) ?></strong>
        </div>
      <?php endif; ?>

      <?php if ($viewerType === 'owner' && isset($finderLabel)): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="user" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i> Nalezce
          </span>
          <strong><?= e($finderLabel) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Messages -->
<div class="card">
  <div class="card-header">
    <h3><i data-lucide="message-circle" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Zpravy</h3>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <div class="conversation-messages" id="messages"
         data-poll-url="/found/<?= e($report->getConversationToken()) ?>/poll"
         data-last-id="<?= !empty($messages) ? $messages[array_key_last($messages)]->getId() : 0 ?>">
      <?php if (empty($messages)): ?>
        <p class="text-muted" style="text-align:center;padding:2rem 0">Zatim zadne zpravy.</p>
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

    <?php if (!$report->isResolved() && !$report->isClosed()): ?>
      <?php if ($viewerType === 'finder'): ?>
        <form method="POST" action="/found/conversation/<?= e($report->getConversationToken()) ?>/message">
      <?php else: ?>
        <form method="POST" action="/found/<?= $report->getId() ?>/message">
      <?php endif; ?>
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <div class="conversation-compose">
            <textarea name="message" rows="2" required placeholder="Napiste zpravu..."></textarea>
            <button type="submit" class="btn btn-primary">
              <i data-lucide="send"></i>
            </button>
          </div>
        </form>

      <!-- Owner-only: Resolve and Close buttons -->
      <?php if ($viewerType === 'owner'): ?>
        <div class="flex gap-sm mt-md mb-md flex-wrap">
          <form method="POST" action="/found/<?= $report->getId() ?>/resolve"
                onsubmit="return confirm('Opravdu jste kolo ziskali zpet? Tim se uzavre tato konverzace a kolo bude oznaceno jako aktivni.')">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-primary">
              <i data-lucide="check-circle"></i> Kolo jsem ziskal zpet
            </button>
          </form>
          <form method="POST" action="/found/<?= $report->getId() ?>/close"
                onsubmit="return confirm('Opravdu uzavrit tuto konverzaci?')">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-ghost">
              <i data-lucide="x-circle"></i> Uzavrit konverzaci
            </button>
          </form>
        </div>
      <?php endif; ?>

    <?php elseif ($report->isClosed()): ?>
      <div class="alert alert-info mt-md mb-md">
        <i data-lucide="info"></i>
        <span>Tato konverzace je uzavrena.</span>
      </div>
    <?php else: ?>
      <div class="alert alert-info mt-md mb-md">
        <i data-lucide="check-circle"></i>
        <span>Tato konverzace je uzavrena — kolo bylo uspesne navraceno majiteli.</span>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($viewerType === 'finder'): ?>
  <div class="alert alert-warning mt-lg">
    <i data-lucide="bookmark"></i>
    <span>Ulozte si odkaz na tuto stranku — je to vas jediny pristup ke konverzaci.</span>
  </div>
<?php endif; ?>

<script>
(function() {
    'use strict';
    var msgContainer = document.getElementById('messages');
    if (msgContainer) {
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }
})();
</script>
