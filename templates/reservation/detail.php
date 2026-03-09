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
      <i data-lucide="arrow-left"></i> Zpět na rezervace
    </a>
  </div>
</div>

<!-- Overdue banner -->
<?php if ($isOwner && $reservation->isOverdue()): ?>
  <div class="overdue-banner mb-lg">
    <i data-lucide="alert-triangle"></i>
    <div>
      <strong>Kolo mělo být vráceno <?= $reservation->getFormattedDateTo() ?>.</strong>
      <p class="mt-xs">Potvrďte vrácení, nebo nahlaste nevrácení.</p>
      <div class="flex gap-sm mt-sm">
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/complete">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="btn btn-primary btn-sm"
                  data-confirm="Potvrzujete, že vypůjčitel vrátil kolo?"
                  data-confirm-ok="Ano, kolo vráceno" data-confirm-class="btn-primary">
            <i data-lucide="check-circle"></i> Kolo vráceno
          </button>
        </form>
        <a href="/reservation/<?= $reservation->getId() ?>/not-returned" class="btn btn-danger btn-sm">
          <i data-lucide="x-circle"></i> Nahlásit nevrácení
        </a>
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
          <i data-lucide="calendar" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i> Termín
        </span>
        <strong><?= $reservation->getDateRangeText() ?></strong>
        <span class="text-muted text-sm">
          (<?= $reservation->getDurationDays() ?> <?= $reservation->getDurationDays() === 1 ? 'den' : ($reservation->getDurationDays() < 5 ? 'dny' : 'dní') ?>)
        </span>
      </div>

      <?php if ($otherUser): ?>
        <div class="reservation-detail-item">
          <span class="text-muted text-sm">
            <i data-lucide="user" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
            <?= $isOwner ? 'Vypůjčitel' : 'Majitel' ?>
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
              <button type="submit" class="btn btn-primary"
                      data-confirm="Opravdu chcete schválit tuto rezervaci?"
                      data-confirm-ok="Schválit" data-confirm-class="btn-primary">
                <i data-lucide="check"></i> Schválit
              </button>
            </form>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/reject">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-danger"
                      data-confirm="Opravdu zamítnout tuto rezervaci?">
                <i data-lucide="x"></i> Zamítnout
              </button>
            </form>
          <?php endif; ?>

          <?php if ($reservation->canBeActivated()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/activate">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-primary"
                      data-confirm="Potvrzujete, že jste předali kolo vypůjčiteli?"
                      data-confirm-ok="Ano, předáno"
                      data-confirm-class="btn-primary">
                <i data-lucide="bike"></i> Kolo předáno — zahájit výpůjčku
              </button>
            </form>
          <?php endif; ?>

          <?php if ($reservation->canBeCompleted()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/complete">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-primary"
                      data-confirm="Potvrzujete, že vypůjčitel vrátil kolo?"
                      data-confirm-ok="Ano, kolo vráceno" data-confirm-class="btn-primary">
                <i data-lucide="check-circle"></i> Kolo vráceno — dokončit
              </button>
            </form>
            <a href="/reservation/<?= $reservation->getId() ?>/not-returned" class="btn btn-danger">
              <i data-lucide="alert-circle"></i> Nahlásit nevrácení
            </a>
          <?php endif; ?>

          <?php if ($reservation->isApproved()): ?>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button type="submit" class="btn btn-outline-danger"
                      data-confirm="Opravdu zrušit tuto rezervaci? Tato akce je nevratná.">
                <i data-lucide="x-circle"></i> Zrušit rezervaci
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
      <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="btn btn-danger"
                data-confirm="Opravdu zrušit svou žádost o rezervaci?">
          <i data-lucide="x-circle"></i> Zrušit mou žádost
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Not returned banner (for borrower — can dispute) -->
<?php if ($reservation->isNotReturned() && !$reservation->isAdminResolved()): ?>
  <div class="card mb-lg">
    <div class="card-body">
      <div class="alert alert-danger">
        <i data-lucide="alert-triangle"></i>
        <div>
          <strong>Kolo bylo nahlášeno jako nevrácené.</strong>
          <p class="mt-xs">Případ čeká na vyřešení správcem.</p>
        </div>
      </div>

      <?php if ($reservation->getNotReturnedReason()): ?>
        <div class="mt-md" style="padding:1rem;background:var(--bg-secondary, #f5f5f0);border-radius:var(--radius-md)">
          <h4 class="mb-xs text-sm text-muted">Popis vlastníka:</h4>
          <p><?= nl2br(e($reservation->getNotReturnedReason())) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($isBorrower && $reservation->canBeDisputed()): ?>
        <div class="mt-md">
          <a href="/reservation/<?= $reservation->getId() ?>/dispute" class="btn btn-warning">
            <i data-lucide="flag"></i> Podat námitku
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Disputed status banner -->
<?php if ($reservation->isDisputed() && !$reservation->isAdminResolved()): ?>
  <div class="card mb-lg">
    <div class="card-body">
      <div class="alert alert-warning">
        <i data-lucide="flag"></i>
        <div>
          <strong>Tato rezervace je ve sporu.</strong>
          <p class="mt-xs">Vypůjčitel podal námitku. Případ čeká na rozhodnutí správce.</p>
        </div>
      </div>

      <?php if ($reservation->getNotReturnedReason()): ?>
        <div class="mt-md" style="padding:1rem;background:var(--bg-secondary, #f5f5f0);border-radius:var(--radius-md)">
          <h4 class="mb-xs text-sm text-muted">Tvrzení vlastníka:</h4>
          <p><?= nl2br(e($reservation->getNotReturnedReason())) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($reservation->getDisputeReason()): ?>
        <div class="mt-md" style="padding:1rem;background:var(--bg-secondary, #f5f5f0);border-radius:var(--radius-md)">
          <h4 class="mb-xs text-sm text-muted">Námitka vypůjčitele:</h4>
          <p><?= nl2br(e($reservation->getDisputeReason())) ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($disputePhotos)): ?>
        <div class="mt-md">
          <h4 class="mb-xs text-sm text-muted">Důkazní fotografie:</h4>
          <div class="photo-preview-grid">
            <?php foreach ($disputePhotos as $photo): ?>
              <img src="/file/dispute-photo/<?= (int) $photo['id'] ?>"
                   alt="Důkaz" style="width:120px;height:90px;object-fit:cover;border-radius:var(--radius-md);cursor:pointer"
                   data-lightbox>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Admin resolution panel -->
<?php if ($currentUser->isAdmin() && $reservation->canBeAdminResolved()): ?>
  <div class="card mb-lg" style="border:2px solid var(--accent)">
    <div class="card-header">
      <h3><i data-lucide="shield" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Rozhodnutí správce</h3>
    </div>
    <div class="card-body">
      <p class="mb-md">Rozhodněte, která strana má pravdu. Toto rozhodnutí je nevratné.</p>

      <?php if ($reservation->getNotReturnedReason()): ?>
        <div class="mb-md" style="padding:1rem;background:var(--bg-secondary, #f5f5f0);border-radius:var(--radius-md)">
          <h4 class="mb-xs text-sm text-muted">Tvrzení vlastníka:</h4>
          <p><?= nl2br(e($reservation->getNotReturnedReason())) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($reservation->getDisputeReason()): ?>
        <div class="mb-md" style="padding:1rem;background:var(--bg-secondary, #f5f5f0);border-radius:var(--radius-md)">
          <h4 class="mb-xs text-sm text-muted">Námitka vypůjčitele:</h4>
          <p><?= nl2br(e($reservation->getDisputeReason())) ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($disputePhotos)): ?>
        <div class="mb-md">
          <h4 class="mb-xs text-sm text-muted">Důkazní fotografie:</h4>
          <div class="photo-preview-grid">
            <?php foreach ($disputePhotos as $photo): ?>
              <img src="/file/dispute-photo/<?= (int) $photo['id'] ?>"
                   alt="Důkaz" style="width:120px;height:90px;object-fit:cover;border-radius:var(--radius-md);cursor:pointer"
                   data-lightbox>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="flex gap-sm flex-wrap">
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/admin-resolve">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="resolution" value="borrower_guilty">
          <button type="submit" class="btn btn-danger"
                  data-confirm="Rozhodnout: vypůjčitel nevrátil kolo. Vypůjčitel bude zablokován a ztratí karmu. Pokračovat?">
            <i data-lucide="user-x"></i> Vypůjčitel nevrátil kolo
          </button>
        </form>
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/admin-resolve">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="resolution" value="owner_guilty">
          <button type="submit" class="btn btn-warning"
                  data-confirm="Rozhodnout: vlastník podal nepravdivé hlášení. Vlastník ztratí karmu. Pokračovat?"
                  data-confirm-class="btn-warning"
                  data-confirm-ok="Potvrdit rozhodnutí">
            <i data-lucide="shield-off"></i> Vlastník podal nepravdivé hlášení
          </button>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Admin resolution result -->
<?php if ($reservation->isAdminResolved()): ?>
  <div class="card mb-lg">
    <div class="card-body">
      <div class="alert <?= $reservation->getAdminResolution() === 'borrower_guilty' ? 'alert-danger' : 'alert-warning' ?>">
        <i data-lucide="gavel"></i>
        <div>
          <strong>Spor vyřešen správcem</strong>
          <p class="mt-xs"><?= e($reservation->getAdminResolutionLabel()) ?></p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Reviews -->
<?php if ($reservation->canBeReviewed()): ?>
  <div class="card mb-lg">
    <div class="card-header">
      <h3><i data-lucide="star" style="width:18px;height:18px;display:inline;vertical-align:-3px"></i> Hodnocení</h3>
    </div>
    <div class="card-body">
      <?php if (!$hasReviewed): ?>
        <p class="mb-md">Ohodnoťte <?= $isOwner ? 'vypůjčitele' : 'majitele' ?>:</p>

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
            <label for="review-comment">Komentář (volitelné)</label>
            <textarea id="review-comment" name="comment" rows="3"
                      placeholder="Popište svou zkušenost..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary">
            <i data-lucide="send"></i> Odeslat hodnocení
          </button>
        </form>

        <p class="text-muted text-sm mt-md">
          <i data-lucide="info" style="width:14px;height:14px;display:inline;vertical-align:-2px"></i>
          Hodnocení se zobrazí až poté, co jej odešlou obě strany.
        </p>
      <?php else: ?>
        <div class="alert alert-info">
          <i data-lucide="check-circle"></i>
          <span>Vaše hodnocení bylo odesláno.</span>
        </div>

        <?php if (empty($reviews)): ?>
          <p class="text-muted text-sm mt-sm">
            Čekáme na hodnocení od druhé strany. Jakmile obě strany odešlou hodnocení, zobrazí se.
          </p>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Revealed reviews -->
      <?php if (!empty($reviews)): ?>
        <hr class="mt-lg mb-lg">
        <h4 class="mb-md">Zveřejněná hodnocení</h4>
        <?php foreach ($reviews as $review): ?>
          <div class="review-item">
            <div class="review-meta">
              <?= $review->getStarsHtml() ?>
              <span class="text-muted text-sm">
                – <?= $review->getReviewerId() === $reservation->getOwnerId() ? 'Majitel' : 'Vypůjčitel' ?>
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
         data-last-id="<?= !empty($messages) ? $messages[array_key_last($messages)]->getId() : 0 ?>"
         data-status="<?= e($reservation->getStatusLabel()) ?>">
      <?php if (empty($messages)): ?>
        <p class="text-muted" style="text-align:center;padding:2rem 0">Zatím žádné zprávy.</p>
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
          <textarea name="message" rows="2" required placeholder="Napište zprávu..."></textarea>
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
