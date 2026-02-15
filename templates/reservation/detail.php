<?php
$isOwner = $reservation->isOwnedBy($currentUser->getId());
$isBorrower = $reservation->isBorrowedBy($currentUser->getId());
$otherUser = $isOwner ? $reservation->getBorrower() : $reservation->getOwner();
$bike = $reservation->getBike();
?>

<h1>Rezervace #<?= $reservation->getId() ?></h1>

<?php if (isset($session) && $session->hasFlash('error')): ?>
    <div class="alert alert-error"><?= e($session->getFlash('error')) ?></div>
<?php endif; ?>
<?php if (isset($session) && $session->hasFlash('success')): ?>
    <div class="alert alert-success"><?= e($session->getFlash('success')) ?></div>
<?php endif; ?>

<!-- ═══ OVERDUE BANNER ═══ -->
<?php if ($isOwner && $reservation->isOverdue()): ?>
    <div class="alert alert-error">
        <strong>⚠ Kolo mělo být vráceno <?= $reservation->getFormattedDateTo() ?>.</strong>
        Potvrdíte vrácení, nebo nahlásíte nevrácení?
        <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/complete" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="btn btn-primary btn-small">Kolo vráceno</button>
            </form>
            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/not-returned" style="display:inline;"
                  onsubmit="return confirm('Opravdu chcete nahlásit nevrácení kola? Vypůjčitel ztratí 10 karma bodů.')">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="btn btn-danger btn-small">Kolo nevráceno</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- ═══ RESERVATION INFO CARD ═══ -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="conversation-header">
        <div class="conversation-meta">
            <?php if ($bike): ?>
                <h2 style="margin: 0;">
                    <a href="/bike/<?= e($bike->getQrHash()) ?>"><?= e($bike->getFullName()) ?></a>
                </h2>
            <?php endif; ?>

            <p style="margin: 0.25rem 0;">
                <span class="badge <?= $reservation->getStatusClass() ?>">
                    <?= e($reservation->getStatusLabel()) ?>
                </span>
            </p>

            <p class="text-muted" style="margin: 0.25rem 0;">
                Termín: <strong><?= $reservation->getDateRangeText() ?></strong>
                (<?= $reservation->getDurationDays() ?> <?= $reservation->getDurationDays() === 1 ? 'den' : ($reservation->getDurationDays() < 5 ? 'dny' : 'dní') ?>)
            </p>

            <?php if ($otherUser): ?>
                <p class="text-muted" style="margin: 0.25rem 0;">
                    <?= $isOwner ? 'Vypůjčitel' : 'Majitel' ?>:
                    <strong><?= e($otherUser->getName()) ?></strong>
                    (<?= e($otherUser->getKarmaLevel()) ?>, <?= $otherUser->getKarmaScore() ?> karma)
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ ACTION BUTTONS ═══ -->
<?php if ($isOwner): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3>Akce majitele</h3>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">

            <?php if ($reservation->canBeApproved()): ?>
                <form method="POST" action="/reservation/<?= $reservation->getId() ?>/approve" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-primary">Schválit</button>
                </form>
                <form method="POST" action="/reservation/<?= $reservation->getId() ?>/reject" style="display:inline;"
                      onsubmit="return confirm('Opravdu zamítnout?')">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-danger">Zamítnout</button>
                </form>
            <?php endif; ?>

            <?php if ($reservation->canBeActivated()): ?>
                <form method="POST" action="/reservation/<?= $reservation->getId() ?>/activate" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Potvrzujete, že jste předali kolo vypůjčiteli?')">
                        Kolo předáno – zahájit výpůjčku
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($reservation->canBeCompleted()): ?>
                <form method="POST" action="/reservation/<?= $reservation->getId() ?>/complete" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-primary">Kolo vráceno – dokončit</button>
                </form>
                <form method="POST" action="/reservation/<?= $reservation->getId() ?>/not-returned" style="display:inline;"
                      onsubmit="return confirm('Opravdu nahlásit nevrácení?')">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-danger">Nahlásit nevrácení</button>
                </form>
            <?php endif; ?>

            <?php if ($reservation->canBeCancelled()): ?>
                <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel" style="display:inline;"
                      onsubmit="return confirm('Opravdu zrušit rezervaci?')">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn">Zrušit rezervaci</button>
                </form>
            <?php endif; ?>

        </div>
    </div>
<?php elseif ($isBorrower && $reservation->canBeCancelled()): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel"
              onsubmit="return confirm('Opravdu zrušit žádost?')">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-danger">Zrušit mou žádost</button>
        </form>
    </div>
<?php endif; ?>

<!-- ═══ REVIEWS SECTION ═══ -->
<?php if ($reservation->canBeReviewed()): ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3>Hodnocení</h3>

        <?php if (!$hasReviewed): ?>
            <p>Ohodnoťte <?= $isOwner ? 'vypůjčitele' : 'majitele' ?>:</p>

            <form method="POST" action="/reservation/<?= $reservation->getId() ?>/review">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

                <div class="form-group star-rating-group">
                    <div class="star-rating" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label>
                                <input type="radio" name="rating" value="<?= $i ?>" required>
                                <span class="star" data-value="<?= $i ?>">☆</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="review-comment">Komentář (volitelné)</label>
                    <textarea id="review-comment" name="comment" rows="3"
                              placeholder="Popište svou zkušenost…"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Odeslat hodnocení</button>
            </form>

            <p class="text-muted text-small" style="margin-top: 0.75rem;">
                Hodnocení se zobrazí až poté, co jej odešlou obě strany.
            </p>

        <?php else: ?>
            <p class="text-muted">Vaše hodnocení bylo odesláno.</p>

            <?php if (empty($reviews)): ?>
                <p class="text-muted text-small">
                    Čekáme na hodnocení od druhé strany. Jakmile obě strany odešlou hodnocení, zobrazí se.
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Revealed reviews -->
        <?php if (!empty($reviews)): ?>
            <hr style="margin: 1rem 0;">
            <h4>Zveřejněná hodnocení</h4>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item" style="margin-bottom: 1rem; padding: 0.75rem; background: var(--bg-secondary, #f9f9f9); border-radius: 6px;">
                    <div>
                        <?= $review->getStarsHtml() ?>
                        <span class="text-muted text-small">
                            – <?= $review->getReviewerId() === $reservation->getOwnerId() ? 'Majitel' : 'Vypůjčitel' ?>
                            (<?= $review->getFormattedDate() ?>)
                        </span>
                    </div>
                    <?php if ($review->getComment()): ?>
                        <p style="margin: 0.5rem 0 0;"><?= e($review->getComment()) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ═══ CONVERSATION ═══ -->
<div class="card">
    <h3>Konverzace</h3>

    <div class="conversation-messages" id="messages">
        <?php if (empty($messages)): ?>
            <p class="text-muted">Zatím žádné zprávy.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message <?= $msg->getSenderClass() ?>">
                    <div class="message-header">
                        <strong><?= e($msg->getSenderLabel()) ?></strong>
                        <span class="message-time"><?= $msg->getFormattedTime() ?></span>
                    </div>
                    <div class="message-body"><?= nl2br(e($msg->getMessage())) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Message form (only for active conversations) -->
    <?php if (!in_array($reservation->getStatus(), ['rejected', 'cancelled'], true)): ?>
        <form method="POST" action="/reservation/<?= $reservation->getId() ?>/message"
              style="margin-top: 1rem;">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="form-group">
                <textarea name="message" rows="2" required
                          placeholder="Napište zprávu…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Odeslat</button>
        </form>
    <?php endif; ?>
</div>

<!-- Navigation -->
<div style="margin-top: 1.5rem;">
    <a href="/reservations">&larr; Zpět na moje rezervace</a>
</div>

<!-- Star rating interactive JS -->
<style>
    .star-rating { display: inline-flex; direction: rtl; font-size: 2rem; gap: 0.1rem; }
    .star-rating label { cursor: pointer; }
    .star-rating input { display: none; }
    .star-rating .star { color: #ddd; transition: color 0.15s; }
    .star-rating label:hover .star,
    .star-rating label:hover ~ label .star { color: #f59e0b; }
    .star-rating input:checked ~ .star,
    .star-rating label:has(input:checked) .star,
    .star-rating label:has(input:checked) ~ label .star { color: #f59e0b; }

    .stars { color: #f59e0b; font-size: 1.1rem; letter-spacing: 1px; }
</style>

<script>
(function() {
    'use strict';

    // Star rating: update visual state on click
    var starContainer = document.getElementById('star-rating');
    if (starContainer) {
        var stars = starContainer.querySelectorAll('.star');
        var inputs = starContainer.querySelectorAll('input[type="radio"]');

        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                var val = parseInt(this.value);
                stars.forEach(function(s) {
                    var sv = parseInt(s.getAttribute('data-value'));
                    s.textContent = sv <= val ? '★' : '☆';
                    s.style.color = sv <= val ? '#f59e0b' : '#ddd';
                });
            });
        });
    }

    // Auto-scroll messages to bottom
    var msgContainer = document.getElementById('messages');
    if (msgContainer) {
        msgContainer.scrollTop = msgContainer.scrollHeight;
    }
})();
</script>