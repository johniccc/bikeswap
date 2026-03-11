<div class="page-header">
  <h1>Konverzace nálezu #<?= $report->getId() ?></h1>
  <div class="page-header-actions">
    <a href="/admin/conversations" class="btn btn-secondary btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na konverzace
    </a>
    <?php if ($report->getBikeId()): ?>
      <a href="/found/<?= $report->getId() ?>/conversation" class="btn btn-ghost btn-sm" target="_blank">
        <i data-lucide="external-link"></i> Konverzace vlastníka
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Info -->
<div class="card mb-lg">
  <div class="card-header"><h3>Informace o nálezu</h3></div>
  <div class="card-body">
    <div class="info-row">
      <span class="info-label">Kolo</span>
      <span class="info-value">
        <?php if ($bike): ?>
          <a href="/admin/bikes/<?= $bike->getId() ?>"><?= e($bike->getFullName()) ?></a>
        <?php elseif ($report->getQrHashScanned()): ?>
          QR: <?= e($report->getQrHashScanned()) ?>
        <?php else: ?>—<?php endif; ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Stav</span>
      <span class="info-value">
        <span class="status-badge <?= e($report->getStatusClass()) ?>"><?= e($report->getStatusLabel()) ?></span>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Místo nálezu</span>
      <span class="info-value"><?= $report->getFoundLocationText() ? e($report->getFoundLocationText()) : '—' ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Datum nálezu</span>
      <span class="info-value"><?= $report->getFoundDate() ? date('d.m.Y', strtotime($report->getFoundDate())) : '—' ?></span>
    </div>
    <?php if ($report->getReporterEmail()): ?>
    <div class="info-row">
      <span class="info-label">E-mail nálezce</span>
      <span class="info-value"><?= e($report->getReporterEmail()) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($report->getDescription()): ?>
    <div class="info-row">
      <span class="info-label">Popis</span>
      <span class="info-value"><?= e($report->getDescription()) ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Conversation -->
<div class="card">
  <div class="card-header">
    <h3>Konverzace</h3>
    <span class="admin-badge"><i data-lucide="shield" style="width:11px;height:11px"></i> Administrátor</span>
  </div>

  <div class="conversation-messages" id="conversation-thread">
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
    <form method="POST" action="/admin/conversation/found/<?= $report->getId() ?>/message"
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

<script>
(function () {
  var t = document.getElementById('conversation-thread');
  if (t) t.scrollTop = t.scrollHeight;
}());
</script>
