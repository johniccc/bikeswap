<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\Bike;
use App\Entity\Reservation;
use App\Entity\User;

/**
 * Repository for the reservations table.
 */
class ReservationRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find reservation by ID, optionally with relations loaded.
     */
    public function findById(int $id, bool $withRelations = false): ?Reservation
    {
        $row = $this->db->fetchOne("SELECT * FROM reservations WHERE id = ?", [$id]);

        if ($row === null) {
            return null;
        }

        $reservation = Reservation::fromRow($row);

        if ($withRelations) {
            $this->loadRelations($reservation);
        }

        return $reservation;
    }

    /**
     * Find reservation by conversation token.
     */
    public function findByToken(string $token, bool $withRelations = false): ?Reservation
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM reservations WHERE conversation_token = ?",
            [$token]
        );

        if ($row === null) {
            return null;
        }

        $reservation = Reservation::fromRow($row);

        if ($withRelations) {
            $this->loadRelations($reservation);
        }

        return $reservation;
    }

    /**
     * Get all reservations where user is the borrower.
     *
     * @return Reservation[]
     */
    public function findByBorrower(int $userId, bool $withRelations = false): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservations WHERE borrower_id = ? ORDER BY created_at DESC",
            [$userId]
        );

        return $this->hydrateList($rows, $withRelations);
    }

    /**
     * Get all reservations where user is the owner.
     *
     * @return Reservation[]
     */
    public function findByOwner(int $userId, bool $withRelations = false): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservations WHERE owner_id = ? ORDER BY created_at DESC",
            [$userId]
        );

        return $this->hydrateList($rows, $withRelations);
    }

    /**
     * Get pending reservations for a specific bike (owner view).
     *
     * @return Reservation[]
     */
    public function findPendingByBikeId(int $bikeId, bool $withRelations = false): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservations WHERE bike_id = ? AND status = 'pending' ORDER BY created_at ASC",
            [$bikeId]
        );

        return $this->hydrateList($rows, $withRelations);
    }

    /**
     * Get overdue active reservations for an owner (past date_to).
     * Used for "return reminder" banners.
     *
     * @return Reservation[]
     */
    public function findOverdueByOwner(int $ownerId, bool $withRelations = false): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservations 
             WHERE owner_id = ? AND status = 'active' AND date_to < CURDATE()
             ORDER BY date_to ASC",
            [$ownerId]
        );

        return $this->hydrateList($rows, $withRelations);
    }

    /**
     * Check if a date range conflicts with existing approved/active reservations.
     *
     * Two ranges [A_from, A_to] and [B_from, B_to] overlap when:
     *   A_from <= B_to AND A_to >= B_from
     *
     * @param int|null $excludeReservationId  Exclude a specific reservation (for re-checks)
     */
    public function hasDateConflict(int $bikeId, string $dateFrom, string $dateTo, ?int $excludeReservationId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM reservations 
                WHERE bike_id = ? 
                AND status IN ('approved', 'active') 
                AND date_from <= ? 
                AND date_to >= ?";
        $params = [$bikeId, $dateTo, $dateFrom];

        if ($excludeReservationId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeReservationId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Get date ranges that are unavailable for a specific bike.
     * Returns array of ['date_from' => ..., 'date_to' => ...] for approved/active reservations.
     */
    public function getUnavailableDateRanges(int $bikeId): array
    {
        return $this->db->fetchAll(
            "SELECT date_from, date_to FROM reservations 
             WHERE bike_id = ? AND status IN ('approved', 'active') 
             ORDER BY date_from ASC",
            [$bikeId]
        );
    }

    /**
     * Create a new reservation. Returns the new ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('reservations', [
            'bike_id'            => $data['bike_id'],
            'borrower_id'        => $data['borrower_id'],
            'owner_id'           => $data['owner_id'],
            'conversation_token' => $data['conversation_token'],
            'date_from'          => $data['date_from'],
            'date_to'            => $data['date_to'],
            'message'            => $data['message'] ?? null,
        ]);
    }

    /**
     * Update reservation status.
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->db->update('reservations', ['status' => $status], 'id = ?', [$id]);
    }

    /**
     * Get bike IDs that are currently reserved (status approved or active, end date >= today).
     */
    public function findUnavailableBikeIds(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT bike_id FROM reservations WHERE status IN ('approved', 'active') AND date_to >= CURDATE()"
        );
        return array_column($rows, 'bike_id');
    }

    /**
     * Count reservations by status for a bike.
     */
    public function countByBikeAndStatus(int $bikeId, string $status): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reservations WHERE bike_id = ? AND status = ?",
            [$bikeId, $status]
        );
    }

    /**
     * Count pending reservations for all bikes of an owner (for dashboard badge).
     */
    public function countPendingByOwner(int $ownerId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reservations WHERE owner_id = ? AND status = 'pending'",
            [$ownerId]
        );
    }

    // ── Internal helpers ───────────────────────────────────

    /**
     * Load bike, borrower, and owner relations onto a reservation.
     */
    private function loadRelations(Reservation $reservation): void
    {
        // Load bike
        $bikeRow = $this->db->fetchOne("SELECT * FROM bikes WHERE id = ?", [$reservation->getBikeId()]);
        if ($bikeRow) {
            $reservation->setBike(Bike::fromRow($bikeRow));
        }

        // Load borrower
        $borrowerRow = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$reservation->getBorrowerId()]);
        if ($borrowerRow) {
            $reservation->setBorrower(User::fromRow($borrowerRow));
        }

        // Load owner
        $ownerRow = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$reservation->getOwnerId()]);
        if ($ownerRow) {
            $reservation->setOwner(User::fromRow($ownerRow));
        }
    }

    /**
     * @return Reservation[]
     */
    private function hydrateList(array $rows, bool $withRelations): array
    {
        $reservations = array_map(fn(array $row) => Reservation::fromRow($row), $rows);

        if ($withRelations) {
            foreach ($reservations as $r) {
                $this->loadRelations($r);
            }
        }

        return $reservations;
    }
}