<?php $needsPublicPadding = ($viewerType === 'finder' && empty($currentUser)); ?>
<?php if ($needsPublicPadding): ?><div class="main-public-padded"><?php endif; ?>

<div class="page-header">
  <h1>
    <?php if ($viewerType === 'owner'): ?>
      Konverzace s nálezcem
    <?php else: ?>
      Konverzace s majitelem kola
    <?php endif; ?>
  </h1>
  <div class="page-header-actions">
    <?php if (isset($currentUser) && $currentUser->hasRole('police')): ?>
      <a href="/found/<?= e((string)$report->getId()) ?>/export-pdf" class="btn btn-secondary btn-sm">
        <i data-lucide="file-down"></i> Export PDF
      </a>
    <?php endif; ?>
    <?php if ($viewerType === 'owner' && $bike): ?>
      <a href="/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> Zpět na detail kola
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
            <i data-lucide="palette" class="icon-inline"></i>
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
            <i data-lucide="map-pin" class="icon-inline"></i> Místo nálezu
          </span>
          <strong><?= e($report->getFoundLocationText()) ?></strong>
        </div>
      <?php endif; ?>

      <?php if ($report->getFoundDate()): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="calendar" class="icon-inline"></i> Datum nálezu
          </span>
          <strong><?= e($report->getFormattedFoundDate()) ?></strong>
        </div>
      <?php endif; ?>

      <?php if ($viewerType === 'owner' && isset($finderLabel)): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="user" class="icon-inline"></i> Nálezce
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
    <h3><i data-lucide="message-circle" class="icon-inline-lg"></i> Zprávy</h3>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <div class="conversation-messages" id="messages"
         data-poll-url="/found/<?= e($report->getConversationToken()) ?>/poll"
         data-last-id="<?= !empty($messages) ? $messages[array_key_last($messages)]->getId() : 0 ?>"
         data-status="<?= e($report->getStatusLabel()) ?>">
      <?php if (empty($messages)): ?>
        <p class="text-muted" style="text-align:center;padding:2rem 0">Zatím žádné zprávy.</p>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <?php
          $senderType = $msg->getSenderType();
          $isMine = isset($viewerType) && $senderType === $viewerType;
          ?>
          <?php $isPoliceMsg = !empty($policeUserIds) && $msg->getSenderUserId() !== null && in_array($msg->getSenderUserId(), $policeUserIds, true); ?>
          <div class="message <?= e($msg->getSenderClass()) ?> <?= $isMine ? 'mine' : '' ?>">
            <div class="message-bubble"><?= nl2br(e($msg->getMessage())) ?></div>
            <?php if (!$msg->isSystemMessage()): ?>
              <div class="message-meta">
                <?= e($msg->getSenderLabel()) ?>
                <?php if ($isPoliceMsg): ?>
                  <span class="police-badge"><i data-lucide="shield" style="width:12px;height:12px;display:inline;vertical-align:-2px"></i> Policie ČR</span>
                <?php endif; ?>
                · <?= e($msg->getFormattedTime()) ?>
              </div>
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
            <textarea name="message" rows="2" required placeholder="Napište zprávu..."></textarea>
            <button type="submit" class="btn btn-primary">
              <i data-lucide="send"></i>
            </button>
          </div>
        </form>

      <!-- Owner-only: Resolve and Close buttons -->
      <?php if ($viewerType === 'owner'): ?>
        <div class="flex gap-sm mt-md mb-md flex-wrap">
          <form method="POST" action="/found/<?= $report->getId() ?>/resolve">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-primary"
                    data-confirm="Opravdu jste kolo získali zpět? Tím se uzavře tato konverzace a kolo bude označeno jako aktivní."
                    data-confirm-ok="Ano, získal jsem kolo zpět" data-confirm-class="btn-primary">
              <i data-lucide="check-circle"></i> Kolo jsem získal zpět
            </button>
          </form>
          <form method="POST" action="/found/<?= $report->getId() ?>/close">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-ghost"
                    data-confirm="Opravdu uzavřít tuto konverzaci?">
              <i data-lucide="x-circle"></i> Uzavřít konverzaci
            </button>
          </form>
        </div>
      <?php endif; ?>

    <?php elseif ($report->isClosed()): ?>
      <div class="alert alert-info mt-md mb-md">
        <i data-lucide="info"></i>
        <span>Tato konverzace je uzavřena.</span>
      </div>
    <?php else: ?>
      <div class="alert alert-info mt-md mb-md">
        <i data-lucide="check-circle"></i>
        <span>Tato konverzace je uzavřena — kolo bylo úspěšně navráceno majiteli.</span>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($viewerType === 'finder' && empty($currentUser)): ?>
  <div class="alert alert-warning mt-lg">
    <i data-lucide="bookmark"></i>
    <span>Uložte si odkaz na tuto stránku — je to váš jediný přístup ke konverzaci.</span>
  </div>
<?php endif; ?>

<?php if ($needsPublicPadding): ?></div><!-- .main-public-padded --><?php endif; ?>

<script src="/js/conversation.js"></script>
