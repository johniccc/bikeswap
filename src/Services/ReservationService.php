<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Reservation;
use App\Entity\ReservationReview;
use App\Repository\ReservationRepository;
use App\Repository\ReservationMessageRepository;
use App\Repository\ReservationReviewRepository;
use App\Repository\BikeRepository;
use App\Core\Database;

/**
 * Service handling the full reservation lifecycle.
 *
 * Lifecycle: pending → approved → active → completed → (reviews)
 * 
 * Key features:
 * - Date conflict detection (approved/active reservations block dates)
 * - Blind mutual reviews (Airbnb-style, revealed when both submit)
 * - Karma integration (applied only when reviews are revealed)
 * - In-app messaging between owner and borrower
 */
class ReservationService
{
    private ReservationRepository $reservationRepo;
    private ReservationMessageRepository $messageRepo;
    private ReservationReviewRepository $reviewRepo;
    private BikeRepository $bikeRepo;
    private NotificationService $notificationService;
    private EmailService $emailService;
    private KarmaService $karmaService;
    private Database $db;

    public function __construct(
        ReservationRepository $reservationRepo,
        ReservationMessageRepository $messageRepo,
        ReservationReviewRepository $reviewRepo,
        BikeRepository $bikeRepo,
        NotificationService $notificationService,
        EmailService $emailService,
        KarmaService $karmaService,
        Database $db
    ) {
        $this->reservationRepo = $reservationRepo;
        $this->messageRepo = $messageRepo;
        $this->reviewRepo = $reviewRepo;
        $this->bikeRepo = $bikeRepo;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
        $this->karmaService = $karmaService;
        $this->db = $db;
    }

    // ── Create ─────────────────────────────────────────────

    /**
     * Create a new reservation request.
     *
     * @throws \RuntimeException On validation failures
     * @return array{reservation_id: int, token: string}
     */
    public function createReservation(
        int $bikeId,
        int $borrowerId,
        string $dateFrom,
        string $dateTo,
        ?string $message
    ): array {
        // 1. Load and validate bike
        $bike = $this->bikeRepo->findById($bikeId);

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isShared()) {
            throw new \RuntimeException('Toto kolo není nabízeno k výpůjčce.', 403);
        }

        if ($bike->isStolen()) {
            throw new \RuntimeException('Toto kolo je označeno jako odcizené.', 403);
        }

        if ($bike->isOwnedBy($borrowerId)) {
            throw new \RuntimeException('Nemůžete si půjčit vlastní kolo.', 403);
        }

        // 2. Validate dates
        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
        $to = \DateTimeImmutable::createFromFormat('Y-m-d', $dateTo);
        $today = new \DateTimeImmutable('today');

        if (!$from || !$to) {
            throw new \RuntimeException('Neplatný formát data.');
        }

        if ($from < $today) {
            throw new \RuntimeException('Datum začátku nemůže být v minulosti.');
        }

        if ($to < $from) {
            throw new \RuntimeException('Datum konce musí být po datu začátku.');
        }

        // Max 30 days
        if ($from->diff($to)->days > 30) {
            throw new \RuntimeException('Maximální délka výpůjčky je 30 dní.');
        }

        // 3. Generate unique conversation token
        $token = bin2hex(random_bytes(32));

        // 4. Create reservation
        $reservationId = $this->reservationRepo->create([
            'bike_id'            => $bikeId,
            'borrower_id'        => $borrowerId,
            'owner_id'           => $bike->getOwnerId(),
            'conversation_token' => $token,
            'date_from'          => $dateFrom,
            'date_to'            => $dateTo,
            'message'            => $message,
        ]);

        // 5. Add initial message if provided
        if ($message !== null && trim($message) !== '') {
            $this->messageRepo->create($reservationId, 'borrower', $borrowerId, $message);
        }

        // 6. Notify owner
        $this->notificationService->notify(
            $bike->getOwnerId(),
            'reservation',
            'Nová žádost o výpůjčku kola ' . $bike->getFullName(),
            "/reservation/{$reservationId}"
        );

        // 7. Send email to owner
        $this->emailService->sendReservationRequest($bike->getOwnerId(), $bike, $reservationId);

        return ['reservation_id' => $reservationId, 'token' => $token];
    }

    // ── Status transitions ─────────────────────────────────

    /**
     * Owner approves a reservation.
     * Checks for date conflicts before approval.
     */
    public function approve(int $reservationId, int $ownerId): void
    {
        $reservation = $this->getAndAuthorize($reservationId, $ownerId, 'owner');

        if (!$reservation->canBeApproved()) {
            throw new \RuntimeException('Tuto rezervaci nelze schválit.');
        }

        // Check for date conflicts with other approved/active reservations
        if ($this->reservationRepo->hasDateConflict(
            $reservation->getBikeId(),
            $reservation->getDateFrom(),
            $reservation->getDateTo(),
            $reservationId // exclude self
        )) {
            throw new \RuntimeException(
                'Termín koliduje s jinou schválenou rezervací. Nejprve zamítněte nebo zrušte kolidující rezervaci.'
            );
        }

        try {
            $this->db->beginTransaction();

            $this->reservationRepo->updateStatus($reservationId, 'approved');

            $this->messageRepo->create($reservationId, 'system', null, 'Rezervace byla schválena majitelem.');

            $this->notificationService->notify(
                $reservation->getBorrowerId(),
                'reservation',
                'Vaše rezervace byla schválena!',
                "/reservation/{$reservationId}"
            );

            // Send email to borrower
            $bike = $this->bikeRepo->findById($reservation->getBikeId());
            if ($bike !== null) {
                $this->emailService->sendReservationStatusChange(
                    $reservation->getBorrowerId(),
                    $bike,
                    $reservationId,
                    'approved',
                    'Vaše rezervace byla schválena!'
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Owner rejects a reservation.
     */
    public function reject(int $reservationId, int $ownerId): void
    {
        $reservation = $this->getAndAuthorize($reservationId, $ownerId, 'owner');

        if (!$reservation->canBeRejected()) {
            throw new \RuntimeException('Tuto rezervaci nelze zamítnout.');
        }

        try {
            $this->db->beginTransaction();

            $this->reservationRepo->updateStatus($reservationId, 'rejected');

            $this->messageRepo->create($reservationId, 'system', null, 'Rezervace byla zamítnuta majitelem.');

            $this->notificationService->notify(
                $reservation->getBorrowerId(),
                'reservation',
                'Vaše rezervace byla zamítnuta.',
                "/reservation/{$reservationId}"
            );

            // Send email to borrower
            $bike = $this->bikeRepo->findById($reservation->getBikeId());
            if ($bike !== null) {
                $this->emailService->sendReservationStatusChange(
                    $reservation->getBorrowerId(),
                    $bike,
                    $reservationId,
                    'rejected',
                    'Vaše rezervace byla zamítnuta.'
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Either party cancels a reservation (before active).
     */
    public function cancel(int $reservationId, int $userId): void
    {
        $reservation = $this->reservationRepo->findById($reservationId);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        if (!$reservation->involvesUser($userId)) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$reservation->canBeCancelled()) {
            throw new \RuntimeException('Tuto rezervaci nelze zrušit.');
        }

        try {
            $this->db->beginTransaction();

            $this->reservationRepo->updateStatus($reservationId, 'cancelled');

            $role = $reservation->getUserRole($userId);
            $cancellerLabel = $role === 'owner' ? 'majitelem' : 'vypůjčitelem';
            $this->messageRepo->create($reservationId, 'system', null, "Rezervace byla zrušena {$cancellerLabel}.");

            // Notify the other party
            $otherUserId = $role === 'owner' ? $reservation->getBorrowerId() : $reservation->getOwnerId();
            $this->notificationService->notify(
                $otherUserId,
                'reservation',
                'Rezervace byla zrušena.',
                "/reservation/{$reservationId}"
            );

            // Send email to the other party
            $bike = $this->bikeRepo->findById($reservation->getBikeId());
            if ($bike !== null) {
                $this->emailService->sendReservationStatusChange(
                    $otherUserId,
                    $bike,
                    $reservationId,
                    'cancelled',
                    'Rezervace byla zrušena.'
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Owner marks reservation as active (borrower picked up the bike).
     */
    public function activate(int $reservationId, int $ownerId): void
    {
        $reservation = $this->getAndAuthorize($reservationId, $ownerId, 'owner');

        if (!$reservation->canBeActivated()) {
            throw new \RuntimeException('Tuto rezervaci nelze aktivovat.');
        }

        try {
            $this->db->beginTransaction();

            $this->reservationRepo->updateStatus($reservationId, 'active');

            $this->messageRepo->create($reservationId, 'system', null, 'Kolo bylo předáno. Výpůjčka začala.');

            $this->notificationService->notify(
                $reservation->getBorrowerId(),
                'reservation',
                'Výpůjčka začala! Nezapomeňte kolo vrátit včas.',
                "/reservation/{$reservationId}"
            );

            // Send email to borrower
            $bike = $this->bikeRepo->findById($reservation->getBikeId());
            if ($bike !== null) {
                $this->emailService->sendReservationStatusChange(
                    $reservation->getBorrowerId(),
                    $bike,
                    $reservationId,
                    'active',
                    'Výpůjčka začala! Nezapomeňte kolo vrátit včas.'
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Owner marks reservation as completed (bike returned).
     */
    public function complete(int $reservationId, int $ownerId): void
    {
        $reservation = $this->getAndAuthorize($reservationId, $ownerId, 'owner');

        if (!$reservation->canBeCompleted()) {
            throw new \RuntimeException('Tuto rezervaci nelze dokončit.');
        }

        try {
            $this->db->beginTransaction();

            $this->reservationRepo->updateStatus($reservationId, 'completed');

            $this->messageRepo->create($reservationId, 'system', null, 'Kolo bylo vráceno. Výpůjčka dokončena. Nyní můžete ohodnotit druhou stranu.');

            // Karma reward for completing
            $this->karmaService->addPoints($reservation->getBorrowerId(), 1, 'Výpůjčka dokončena');
            $this->karmaService->addPoints($reservation->getOwnerId(), 1, 'Výpůjčka dokončena (sdílení kola)');

            // Notify both to leave reviews
            $this->notificationService->notify(
                $reservation->getBorrowerId(),
                'review',
                'Výpůjčka dokončena – ohodnoťte majitele.',
                "/reservation/{$reservationId}"
            );

            $this->notificationService->notify(
                $reservation->getOwnerId(),
                'review',
                'Výpůjčka dokončena – ohodnoťte vypůjčitele.',
                "/reservation/{$reservationId}"
            );

            // Send emails to both parties
            $bike = $this->bikeRepo->findById($reservation->getBikeId());
            if ($bike !== null) {
                $this->emailService->sendReservationStatusChange(
                    $reservation->getBorrowerId(),
                    $bike,
                    $reservationId,
                    'completed',
                    'Výpůjčka dokončena – ohodnoťte majitele.'
                );
                $this->emailService->sendReservationStatusChange(
                    $reservation->getOwnerId(),
                    $bike,
                    $reservationId,
                    'completed',
                    'Výpůjčka dokončena – ohodnoťte vypůjčitele.'
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Owner reports that bike was not returned.
     */
    public function reportNotReturned(int $reservationId, int $ownerId): void
    {
        $reservation = $this->getAndAuthorize($reservationId, $ownerId, 'owner');

        if (!$reservation->canReportNotReturned()) {
            throw new \RuntimeException('Nelze nahlásit nevrácení.');
        }

        try {
            $this->db->beginTransaction();

            $this->reservationRepo->updateStatus($reservationId, 'not_returned');

            $this->messageRepo->create($reservationId, 'system', null, '⚠ Majitel nahlásil, že kolo nebylo vráceno.');

            // Karma penalty for borrower
            $this->karmaService->addPoints($reservation->getBorrowerId(), -10, 'Kolo nevráceno');

            $this->notificationService->notify(
                $reservation->getBorrowerId(),
                'warning',
                '⚠ Majitel nahlásil, že jste nevrátili kolo!',
                "/reservation/{$reservationId}"
            );

            // Send email to borrower
            $bike = $this->bikeRepo->findById($reservation->getBikeId());
            if ($bike !== null) {
                $this->emailService->sendReservationStatusChange(
                    $reservation->getBorrowerId(),
                    $bike,
                    $reservationId,
                    'not_returned',
                    '⚠ Majitel nahlásil, že jste nevrátili kolo!'
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Messaging ──────────────────────────────────────────

    /**
     * Send a message in a reservation conversation.
     */
    public function sendMessage(int $reservationId, int $userId, string $message): void
    {
        $reservation = $this->reservationRepo->findById($reservationId);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        if (!$reservation->involvesUser($userId)) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $senderType = $reservation->getUserRole($userId);

        $this->messageRepo->create($reservationId, $senderType, $userId, $message);

        // Notify other party
        $otherUserId = $senderType === 'owner'
            ? $reservation->getBorrowerId()
            : $reservation->getOwnerId();

        $this->notificationService->notify(
            $otherUserId,
            'message',
            'Nová zpráva v konverzaci k rezervaci.',
            "/reservation/{$reservationId}"
        );

        // Send email to other party
        $bike = $this->bikeRepo->findById($reservation->getBikeId());
        if ($bike !== null) {
            $this->emailService->sendReservationMessage($otherUserId, $bike, $reservationId);
        }
    }

    // ── Reviews ────────────────────────────────────────────

    /**
     * Submit a blind review.
     *
     * When both parties have submitted, all reviews for the reservation
     * are revealed and karma is applied.
     */
    public function submitReview(
        int $reservationId,
        int $reviewerId,
        int $rating,
        ?string $comment
    ): void {
        $reservation = $this->reservationRepo->findById($reservationId);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        if (!$reservation->involvesUser($reviewerId)) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$reservation->canBeReviewed()) {
            throw new \RuntimeException('Tuto rezervaci nelze hodnotit.');
        }

        // Check if already reviewed
        $existing = $this->reviewRepo->findByReservationAndReviewer($reservationId, $reviewerId);
        if ($existing !== null) {
            throw new \RuntimeException('Již jste tuto rezervaci ohodnotili.');
        }

        // Determine who is being reviewed
        $reviewedUserId = $reservation->isOwnedBy($reviewerId)
            ? $reservation->getBorrowerId()
            : $reservation->getOwnerId();

        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new \RuntimeException('Hodnocení musí být 1–5.');
        }

        $this->db->beginTransaction();

        try {
            // 1. Create the review (hidden)
            $this->reviewRepo->create([
                'reservation_id'  => $reservationId,
                'reviewer_id'     => $reviewerId,
                'reviewed_user_id' => $reviewedUserId,
                'rating'          => $rating,
                'comment'         => $comment,
            ]);

            // 2. Check if the other party already reviewed
            if ($this->reviewRepo->hasOtherReview($reservationId, $reviewerId)) {
                // Both reviews exist → reveal all
                $this->reviewRepo->revealAllForReservation($reservationId);

                // Apply karma for BOTH reviews
                $allReviews = $this->reviewRepo->findRevealedByReservation($reservationId);
                foreach ($allReviews as $review) {
                    $karmaPoints = ReservationReview::karmaForRating($review->getRating());
                    if ($karmaPoints !== 0) {
                        $this->karmaService->addPoints(
                            $review->getReviewedUserId(),
                            $karmaPoints,
                            "Hodnocení z výpůjčky ({$review->getRating()}★)"
                        );
                    }
                }

                // Notify both that reviews are now visible
                $this->notificationService->notify(
                    $reservation->getBorrowerId(),
                    'review',
                    'Obě hodnocení jsou nyní viditelná!',
                    "/reservation/{$reservationId}"
                );
                $this->notificationService->notify(
                    $reservation->getOwnerId(),
                    'review',
                    'Obě hodnocení jsou nyní viditelná!',
                    "/reservation/{$reservationId}"
                );

                // System message
                $this->messageRepo->create(
                    $reservationId, 'system', null,
                    'Obě strany odeslaly hodnocení. Recenze jsou nyní viditelné.'
                );
            } else {
                // Only one review so far, notify the other party to review too
                $otherUserId = $reservation->isOwnedBy($reviewerId)
                    ? $reservation->getBorrowerId()
                    : $reservation->getOwnerId();

                $this->notificationService->notify(
                    $otherUserId,
                    'review',
                    'Druhá strana vás ohodnotila. Ohodnoťte ji také, aby se recenze zobrazily.',
                    "/reservation/{$reservationId}"
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Queries ────────────────────────────────────────────

    /**
     * Get unavailable date ranges for a bike (JSON endpoint for calendar).
     */
    public function getUnavailableDates(int $bikeId): array
    {
        return $this->reservationRepo->getUnavailableDateRanges($bikeId);
    }

    /**
     * Check if a user has already reviewed a reservation.
     */
    public function hasUserReviewed(int $reservationId, int $userId): bool
    {
        return $this->reviewRepo->findByReservationAndReviewer($reservationId, $userId) !== null;
    }

    // ── Internal helpers ───────────────────────────────────

    /**
     * Load reservation, verify it exists and user has the required role.
     */
    private function getAndAuthorize(int $reservationId, int $userId, string $requiredRole): Reservation
    {
        $reservation = $this->reservationRepo->findById($reservationId);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        $hasAccess = match ($requiredRole) {
            'owner' => $reservation->isOwnedBy($userId),
            'borrower' => $reservation->isBorrowedBy($userId),
            default => $reservation->involvesUser($userId),
        };

        if (!$hasAccess) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        return $reservation;
    }
}