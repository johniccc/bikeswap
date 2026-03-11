<div class="page-header">
  <h1>Konverzace rezervace #<?= $reservation->getId() ?></h1>
  <div class="page-header-actions">
    <a href="/admin/conversations" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na konverzace
    </a>
    <a href="/reservation/<?= $reservation->getId() ?>" class="btn btn-ghost btn-sm" target="_blank">
      <i data-lucide="external-link"></i> Detail rezervace
    </a>
  </div>
</div>

<!-- Info -->
<div class="card mb-lg">
  <div class="card-header"><h3>Informace o rezervaci</h3></div>
  <div class="card-body">
    <div class="info-row">
      <span class="info-label">Kolo</span>
      <span class="info-value">
        <?php if ($bike): ?>
          <a href="/admin/bikes/<?= $bike->getId() ?>"><?= e($bike->getFullName()) ?></a>
        <?php else: ?>—<?php endif; ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Vlastník</span>
      <span class="info-value">
        <?php if ($owner): ?>
          <a href="/admin/users/<?= $owner->getId() ?>"><?= e($owner->getName()) ?></a>
        <?php else: ?>—<?php endif; ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Výpůjčník</span>
      <span class="info-value">
        <?php if ($borrower): ?>
          <a href="/admin/users/<?= $borrower->getId() ?>"><?= e($borrower->getName()) ?></a>
        <?php else: ?>—<?php endif; ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Stav</span>
      <span class="info-value">
        <span class="status-badge status-<?= e($reservation->getStatus()) ?>"><?= e($reservation->getStatusLabel()) ?></span>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Termín</span>
      <span class="info-value">
        <?= date('d.m.Y', strtotime($reservation->getDateFrom())) ?> — <?= date('d.m.Y', strtotime($reservation->getDateTo())) ?>
      </span>
    </div>
  </div>
</div>

<!-- Conversation -->
<div class="card">
  <div class="card-header">
    <h3>Konverzace</h3>
    <span class="admin-badge"><i data-lucide="shield" style="width:11px;height:11px"></i> Administrátor</span>
  </div>

  <div class="conversation-thread" id="conversation-thread">
    <?php if (empty($messages)): ?>
      <div class="conversation-empty text-muted text-center" style="padding:2rem">
        <i data-lucide="message-square" style="width:32px;height:32px;margin-bottom:0.5rem"></i>
        <p>Zatím žádné zprávy.</p>
      </div>
    <?php else: ?>
      <?php foreach ($messages as $msg): ?>
        <?php $isAdmin = $msg->getSenderType() === 'admin'; ?>
        <div class="message <?= $msg->getSenderClass() ?> <?= $isAdmin ? 'mine' : '' ?>">
          <div class="message-bubble"><?= nl2br(e($msg->getMessage())) ?></div>
          <?php if (!$msg->isSystemMessage()): ?>
            <div class="message-meta">
              <?= e($msg->getSenderLabel()) ?>
              <?php if ($isAdmin): ?>
                <span class="admin-badge" style="font-size:0.65rem"><i data-lucide="shield" style="width:10px;height:10px"></i> Admin</span>
              <?php endif; ?>
              · <?= e($msg->getFormattedTime()) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="conversation-compose">
    <form method="POST" action="/admin/conversation/reservation/<?= $reservation->getId() ?>/message"
          style="display:flex;gap:0.5rem;width:100%">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <textarea name="message" class="form-control" placeholder="Napište zprávu jako administrátor…"
                rows="2" style="flex:1;resize:vertical" required></textarea>
      <button type="submit" class="btn btn-primary" style="align-self:flex-end">
        <i data-lucide="send"></i> Odeslat
      </button>
    </form>
  </div>
</div>
