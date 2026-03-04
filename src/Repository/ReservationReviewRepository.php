<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\ReservationReview;
use App\Entity\User;

/**
 * Repository for the reservation_reviews table.
 *
 * Handles the blind mutual review system:
 * Reviews stay hidden until both parties submit.
 */
class ReservationReviewRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a specific review by reservation + reviewer.
     */
    public function findByReservationAndReviewer(int $reservationId, int $reviewerId): ?ReservationReview
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM reservation_reviews WHERE reservation_id = ? AND reviewer_id = ?",
            [$reservationId, $reviewerId]
        );

        return $row ? ReservationReview::fromRow($row) : null;
    }

    /**
     * Get all revealed reviews for a reservation (for display).
     *
     * @return ReservationReview[]
     */
    public function findRevealedByReservation(int $reservationId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservation_reviews WHERE reservation_id = ? AND is_revealed = 1",
            [$reservationId]
        );

        return array_map(fn(array $row) => ReservationReview::fromRow($row), $rows);
    }

    /**
     * Get all revealed reviews about a specific user (for profile).
     *
     * @return ReservationReview[]
     */
    public function findRevealedAboutUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT rr.*, u.name as reviewer_name FROM reservation_reviews rr
             JOIN users u ON u.id = rr.reviewer_id
             WHERE rr.reviewed_user_id = ? AND rr.is_revealed = 1
             ORDER BY rr.created_at DESC",
            [$userId]
        );

        $reviews = [];
        foreach ($rows as $row) {
            $review = ReservationReview::fromRow($row);
            // Optionally load reviewer (we have name from JOIN)
            $reviews[] = $review;
        }

        return $reviews;
    }

    /**
     * Get average rating for a user (only revealed reviews).
     */
    public function getAverageRatingForUser(int $userId): ?float
    {
        $avg = $this->db->fetchColumn(
            "SELECT AVG(rating) FROM reservation_reviews 
             WHERE reviewed_user_id = ? AND is_revealed = 1",
            [$userId]
        );

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * Count reviews for a reservation.
     */
    public function countByReservation(int $reservationId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reservation_reviews WHERE reservation_id = ?",
            [$reservationId]
        );
    }

    /**
     * Check if the other party has already submitted a review.
     */
    public function hasOtherReview(int $reservationId, int $currentReviewerId): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reservation_reviews 
             WHERE reservation_id = ? AND reviewer_id != ?",
            [$reservationId, $currentReviewerId]
        ) > 0;
    }

    /**
     * Get reservation IDs that a user has reviewed.
     *
     * @return int[]
     */
    public function getReviewedReservationIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT reservation_id FROM reservation_reviews WHERE reviewer_id = ?",
            [$userId]
        );

        return array_map(fn(array $row) => (int) $row['reservation_id'], $rows);
    }

    /**
     * Create a new review. Returns the new ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('reservation_reviews', [
            'reservation_id'  => $data['reservation_id'],
            'reviewer_id'     => $data['reviewer_id'],
            'reviewed_user_id' => $data['reviewed_user_id'],
            'rating'          => $data['rating'],
            'comment'         => $data['comment'] ?? null,
            'is_revealed'     => 0, // Always start hidden
        ]);
    }

    /**
     * Reveal all reviews for a reservation (called when both exist).
     */
    public function revealAllForReservation(int $reservationId): void
    {
        $this->db->update(
            'reservation_reviews',
            ['is_revealed' => 1],
            'reservation_id = ?',
            [$reservationId]
        );
    }
}