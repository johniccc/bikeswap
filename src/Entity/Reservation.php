<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Reservation entity.
 *
 * Represents a bike borrowing request with full lifecycle:
 * pending → approved → active → completed → (reviews)
 *    ↓         ↓                    ↓
 * rejected  cancelled          not_returned
 */
class Reservation
{
    private int $id;
    private int $bikeId;
    private int $borrowerId;
    private int $ownerId;
    private string $conversationToken;
    private string $dateFrom;
    private string $dateTo;
    private ?string $message;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    // ── Eagerly loaded relations ───────────────────────────
    private ?Bike $bike = null;
    private ?User $borrower = null;
    private ?User $owner = null;

    /** @var ReservationReview[] */
    private array $reviews = [];

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $r = new self();

        $r->id                = (int) $row['id'];
        $r->bikeId            = (int) $row['bike_id'];
        $r->borrowerId        = (int) $row['borrower_id'];
        $r->ownerId           = (int) $row['owner_id'];
        $r->conversationToken = $row['conversation_token'];
        $r->dateFrom          = $row['date_from'];
        $r->dateTo            = $row['date_to'];
        $r->message           = $row['message'] ?? null;
        $r->status            = $row['status'];
        $r->createdAt         = $row['created_at'];
        $r->updatedAt         = $row['updated_at'];

        return $r;
    }

    // ── Getters ────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getBikeId(): int { return $this->bikeId; }
    public function getBorrowerId(): int { return $this->borrowerId; }
    public function getOwnerId(): int { return $this->ownerId; }
    public function getConversationToken(): string { return $this->conversationToken; }
    public function getDateFrom(): string { return $this->dateFrom; }
    public function getDateTo(): string { return $this->dateTo; }
    public function getMessage(): ?string { return $this->message; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    // ── Relations ──────────────────────────────────────────

    public function getBike(): ?Bike { return $this->bike; }
    public function setBike(?Bike $bike): void { $this->bike = $bike; }

    public function getBorrower(): ?User { return $this->borrower; }
    public function setBorrower(?User $user): void { $this->borrower = $user; }

    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $user): void { $this->owner = $user; }

    /** @return ReservationReview[] */
    public function getReviews(): array { return $this->reviews; }
    /** @param ReservationReview[] $reviews */
    public function setReviews(array $reviews): void { $this->reviews = $reviews; }

    // ── Role checks ────────────────────────────────────────

    public function isOwnedBy(int $userId): bool
    {
        return $this->ownerId === $userId;
    }

    public function isBorrowedBy(int $userId): bool
    {
        return $this->borrowerId === $userId;
    }

    public function involvesUser(int $userId): bool
    {
        return $this->isOwnedBy($userId) || $this->isBorrowedBy($userId);
    }

    /**
     * Get role label for a given user in this reservation.
     */
    public function getUserRole(int $userId): string
    {
        if ($this->isOwnedBy($userId)) return 'owner';
        if ($this->isBorrowedBy($userId)) return 'borrower';
        return 'none';
    }

    // ── Status lifecycle checks ────────────────────────────

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isNotReturned(): bool { return $this->status === 'not_returned'; }

    /** Can the owner approve this reservation? */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /** Can the owner reject this reservation? */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /** Can either party cancel? */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved'], true);
    }

    /** Can the owner mark as active (borrower picked up)? */
    public function canBeActivated(): bool
    {
        return $this->status === 'approved';
    }

    /** Can the owner mark as completed (bike returned)? */
    public function canBeCompleted(): bool
    {
        return $this->status === 'active';
    }

    /** Can the owner report non-return? */
    public function canReportNotReturned(): bool
    {
        return $this->status === 'active';
    }

    /** Can this reservation receive reviews? */
    public function canBeReviewed(): bool
    {
        return in_array($this->status, ['completed', 'not_returned'], true);
    }

    /**
     * Is the end date past today? (for "return reminder" logic)
     */
    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && strtotime($this->dateTo) < strtotime('today');
    }

    // ── Presentation methods ───────────────────────────────

    public function getFormattedDateFrom(): string
    {
        return date('d/m/Y', strtotime($this->dateFrom));
    }

    public function getFormattedDateTo(): string
    {
        return date('d/m/Y', strtotime($this->dateTo));
    }

    public function getDateRangeText(): string
    {
        return $this->getFormattedDateFrom() . ' – ' . $this->getFormattedDateTo();
    }

    public function getDurationDays(): int
    {
        $from = new \DateTimeImmutable($this->dateFrom);
        $to = new \DateTimeImmutable($this->dateTo);

        return (int) $from->diff($to)->days + 1;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending'      => 'Čeká na schválení',
            'approved'     => 'Schváleno',
            'rejected'     => 'Zamítnuto',
            'active'       => 'Probíhá',
            'completed'    => 'Dokončeno',
            'cancelled'    => 'Zrušeno',
            'not_returned' => 'Kolo nevráceno',
            default        => 'Neznámý',
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'pending'      => 'status-found',       // yellow/orange
            'approved'     => 'status-active',       // green
            'rejected'     => 'status-stolen',       // red
            'active'       => 'status-active',       // green
            'completed'    => 'status-recovered',    // blue-green
            'cancelled'    => 'status-unknown',      // grey
            'not_returned' => 'status-stolen',       // red
            default        => 'status-unknown',
        };
    }
}