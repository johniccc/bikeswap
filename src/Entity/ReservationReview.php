<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * ReservationReview entity.
 *
 * Implements blind mutual review system (Airbnb-style):
 * - Both parties can submit reviews after completion
 * - Reviews are hidden (is_revealed = 0) until BOTH submit
 * - Once both exist, both become visible and karma is applied
 */
class ReservationReview
{
    private int $id;
    private int $reservationId;
    private int $reviewerId;
    private int $reviewedUserId;
    private int $rating; // 1–5
    private ?string $comment;
    private bool $isRevealed;
    private string $createdAt;

    // Eagerly loaded
    private ?User $reviewer = null;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $r = new self();

        $r->id             = (int) $row['id'];
        $r->reservationId  = (int) $row['reservation_id'];
        $r->reviewerId     = (int) $row['reviewer_id'];
        $r->reviewedUserId = (int) $row['reviewed_user_id'];
        $r->rating         = (int) $row['rating'];
        $r->comment        = $row['comment'] ?? null;
        $r->isRevealed     = (bool) ($row['is_revealed'] ?? false);
        $r->createdAt      = $row['created_at'];

        return $r;
    }

    // ── Getters ────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getReservationId(): int { return $this->reservationId; }
    public function getReviewerId(): int { return $this->reviewerId; }
    public function getReviewedUserId(): int { return $this->reviewedUserId; }
    public function getRating(): int { return $this->rating; }
    public function getComment(): ?string { return $this->comment; }
    public function isRevealed(): bool { return $this->isRevealed; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function getReviewer(): ?User { return $this->reviewer; }
    public function setReviewer(?User $user): void { $this->reviewer = $user; }

    // ── Presentation ───────────────────────────────────────

    /**
     * Render star rating as HTML string (★ and ☆).
     */
    public function getStarsHtml(): string
    {
        $filled = str_repeat('★', $this->rating);
        $empty = str_repeat('☆', 5 - $this->rating);

        return '<span class="stars">' . $filled . $empty . '</span>';
    }

    public function getFormattedDate(): string
    {
        return date('j. n. Y', strtotime($this->createdAt));
    }

    /**
     * Get karma impact for this rating.
     * Easily adjustable algorithm — change this one method.
     */
    public static function karmaForRating(int $rating): int
    {
        return match ($rating) {
            5 => 3,
            4 => 1,
            3 => 0,
            2 => -1,
            1 => -3,
            default => 0,
        };
    }
}