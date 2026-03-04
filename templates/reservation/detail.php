<?php
$isOwner = $reservation->isOwnedBy($currentUser->getId());
$isBorrower = $reservation->isBorrowedBy($currentUser->getId());
$otherUser = $isOwner ? $reservation->getBorrower() : $reservation->getOwner();
$bike = $reservation->getBike();
?>

<div class="page-header">
  <h1>Rezervace #<?= $reservation->getId() ?></h1>
  <div class="page-header-actions">
    <a href="/reservations" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpet na rezervace
    </a>
  </div>
</div>

<!-- Overdue banner -->
<?php if ($isOwner && $reservation->isOverdue()): ?>
  <div class="overdue-banner mb-lg">
    <i data-lucide="alert-triangle"></i>
    <div>
      <strong>Kolo melo byt vraceno <?= $reservation->getFormattedDateTo() ?>.</strong>
      <p class="mt-xs">Potvrdite vraceni, nebo nahlasite nevraceni?</p>
      <div class="flex gap-sm mt-sm">
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/complete">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="btn btn-primary btn-sm">
            <i data-lucide="check-circle"></i> Kolo vraceno
          </button>
        </form>
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/not-returned"
              onsubmit="return confirm('Opravdu chcete nahlasit nevraceni kola? Vypujcitel ztrati 10 karma bodu.')">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="btn btn-danger btn-sm">
            <i data-lucide="x-circle"></i> Kolo nevraceno
          </button>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Reservation info card -->
<div class="card mb-lg">
  <div class="card-body">
    <div class="flex gap-lg items-center flex-wrap">
      <?php if ($bike): ?>
        <?php $photo = $bike->getPrimaryPhoto(); ?>
        <?php if ($photo): ?>
          <img src="<?= e($photo->getUrl()) ?>" alt="<?= e($bike->getFullName()) ?>"
               style="width:100px;height:75px;object-fit:cover;border-radius:var(--radius-md)">
        <?php endif; ?>
        <div>
          <h3 style="margin-bottom:0.15rem">
            <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
          </h3>
          <p class="text-muted text-sm">
            <i data-lucide="palette" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
            <?= e($bike->getColor()) ?>
          </p>
        </div>
      <?php endif; ?>
      <div style="margin-left:auto">
        <span class="status-badge <?= $reservation->getStatusClass() ?>">
          <?= e($reservation->getStatusLabel()) ?>
        </span>
      </div>
    </div>

    <div class="reservation-detail-grid mt-lg">
      <div class="reservation-detail-item">
        <span class="text-muted text-sm">
          <i data-lucide="calendar" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i> Termin
        </span>
        <strong><?= $reservation->getDateRangeText() ?></strong>
        <span class="text-muted text-sm">
          (<?= $reservation->getDurationDays() ?> <?= $reservation->getDurationDays() === 1 ? 'den' : ($reservation->getDurationDays() < 5 ? 'dny' : 'dni') ?>)
        </span>
      </div>

      <?php if ($otherUser): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="user" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
            <?= $isOwner ? 'Vypujcitel' : 'Majitel' ?>
          </span>
          <strong><?= e($otherUser->getName()) ?></strong>
          <span class="text-muted text-sm">
            <?= e($otherUser->getKarmaLevel()) ?> (<?= $otherUser->getKarmaScore() ?> karma)
          </span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Owner actions -->
<?php if ($isOwner): ?>
  <?php
    $hasActions = $reservation->canBeApproved() || $reservation->canBeActivated()
                  || $reservation->canBeCompleted() || $reservation->isApproved();
  ?>
  <?php if ($hasActions): ?>
    <div class="card mb-lg">
      <div class="card-header">
        <h3><i data-lucide="settings" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Akce majitele</h3>
      </div>
      <div class="card-body">
        <div class="flex gap-sm flex-wrap">

          <?php if ($reservation->canBeApproved()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/approve">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-primary">
                <i data-lucide="check"></i> Schvalit
              </button>
            </form>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/reject"
                  onsubmit="return confirm('Opravdu zamitnout?')">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-danger">
                <i data-lucide="x"></i> Zamitnout
              </button>
            </form>
          <?php endif; ?>

          <?php if ($reservation->canBeActivated()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/activate">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-primary"
                      onclick="return confirm('Potvrzujete, ze jste predali kolo vypujciteli?')">
                <i data-lucide="bike"></i> Kolo predano - zahajit vypujcku
              </button>
            </form>
          <?php endif; ?>

          <?php if ($reservation->canBeCompleted()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/complete">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-primary">
                <i data-lucide="check-circle"></i> Kolo vraceno - dokoncit
              </button>
            </form>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/not-returned"
                  onsubmit="return confirm('Opravdu nahlasit nevraceni?')">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-danger">
                <i data-lucide="alert-circle"></i> Nahlasit nevraceni
              </button>
            </form>
          <?php endif; ?>

          <?php if ($reservation->isApproved()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel"
                  onsubmit="return confirm('Opravdu zrusit rezervaci?')">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-outline-danger">
                <i data-lucide="x-circle"></i> Zrusit rezervaci
              </button>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!-- Borrower cancel -->
<?php if ($isBorrower && $reservation->canBeCancelled()): ?>
  <div class="card mb-lg">
    <div class="card-body">
      <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel"
            onsubmit="return confirm('Opravdu zrusit zadost?')">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="btn btn-danger">
          <i data-lucide="x-circle"></i> Zrusit mou zadost
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Borrower dispute (not_returned) -->
<?php if ($isBorrower && $reservation->getStatus() === 'not_returned'): ?>
  <div class="card mb-lg">
    <div class="card-body">
      <div class="alert alert-warning">
        <i data-lucide="alert-triangle"></i>
        <div>
          <strong>Vlastnik nahlasil, ze kolo nebylo vraceno.</strong>
          <p class="mt-xs">Pokud kolo skutecne vratite, kontaktujte vlastnika. Pokud je situace jinak, muzete podat namitku.</p>
        </div>
      </div>
      <form method="POST" action="/reservation/<?= $reservation->getId() ?>/dispute"
            onsubmit="return confirm('Opravdu chcete podat namitku? Vlastnik bude upozornen.')" class="mt-md">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="btn btn-warning">
          <i data-lucide="flag"></i> Podat namitku
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Reviews -->
<?php if ($reservation->canBeReviewed()): ?>
  <div class="card mb-lg">
    <div class="card-header">
      <h3><i data-lucide="star" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Hodnoceni</h3>
    </div>
    <div class="card-body">
      <?php if (!$hasReviewed): ?>
        <p class="mb-md">Ohodnodte <?= $isOwner ? 'vypujcitele' : 'majitele' ?>:</p>

        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/review">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

          <div class="form-group star-rating-group">
            <div class="star-rating" id="star-rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                <label for="star<?= $i ?>" data-value="<?= $i ?>">
                  <i data-lucide="star"></i>
                </label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="form-group">
            <label for="review-comment">Komentar (volitelne)</label>
            <textarea id="review-comment" name="comment" rows="3"
                      placeholder="Popiste svou zkusenost..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary">
            <i data-lucide="send"></i> Odeslat hodnoceni
          </button>
        </form>

        <p class="text-muted text-sm mt-md">
          <i data-lucide="info" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
          Hodnoceni se zobrazi az pote, co jej odeslou obe strany.
        </p>
      <?php else: ?>
        <div class="alert alert-info">
          <i data-lucide="check-circle"></i>
          <span>Vase hodnoceni bylo odeslano.</span>
        </div>

        <?php if (empty($reviews)): ?>
          <p class="text-muted text-sm mt-sm">
            Cekame na hodnoceni od druhe strany. Jakmile obe strany odeslou hodnoceni, zobrazi se.
          </p>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Revealed reviews -->
      <?php if (!empty($reviews)): ?>
        <hr class="mt-lg mb-lg">
        <h4 class="mb-md">Zverejnena hodnoceni</h4>
        <?php foreach ($reviews as $review): ?>
          <div class="review-item">
            <div class="review-meta">
              <?= $review->getStarsHtml() ?>
              <span class="text-muted text-sm">
                – <?= $review->getReviewerId() === $reservation->getOwnerId() ? 'Majitel' : 'Vypujcitel' ?>
                (<?= $review->getFormattedDate() ?>)
              </span>
            </div>
            <?php if ($review->getComment()): ?>
              <p class="review-comment"><?= e($review->getComment()) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Conversation -->
<div class="card">
  <div class="card-header">
    <h3><i data-lucide="message-circle" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Konverzace</h3>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <div class="conversation-messages" id="messages"
         data-poll-url="/reservation/<?= $reservation->getId() ?>/poll"
         data-last-id="<?= !empty($messages) ? $messages[array_key_last($messages)]->getId() : 0 ?>">
      <?php if (empty($messages)): ?>
        <p class="text-muted" style="text-align:center;padding:2rem 0">Zatim zadne zpravy.</p>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <?php
          $senderType = $msg->getSenderType();
          $isMine = ($isOwner && $senderType === 'owner') || (!$isOwner && $senderType === 'borrower');
          ?>
          <div class="message <?= $msg->getSenderClass() ?> <?= $isMine ? 'mine' : '' ?>">
            <div class="message-bubble"><?= nl2br(e($msg->getMessage())) ?></div>
            <?php if (!$msg->isSystemMessage()): ?>
              <div class="message-meta"><?= e($msg->getSenderLabel()) ?> · <?= e($msg->getFormattedTime()) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if (!in_array($reservation->getStatus(), ['rejected', 'cancelled'], true)): ?>
      <form method="POST" action="/reservation/<?= $reservation->getId() ?>/message">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="conversation-compose">
          <textarea name="message" rows="2" required placeholder="Napiste zpravu..."></textarea>
          <button type="submit" class="btn btn-primary">
            <i data-lucide="send"></i>
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
(function() {
    'use strict';
    var msgContainer = document.getElementById('messages');
    if (msgContainer) {
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }
})();
</script>
