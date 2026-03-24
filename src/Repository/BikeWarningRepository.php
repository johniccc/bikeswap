<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\BikeWarning;

/**
 * Repository for the bike_warnings table.
 *
 * Manages the warning timeline for bikes (warnings, owner pickups, seizures).
 */
class BikeWarningRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a warning by its ID.
     */
    public function findById(int $id): ?BikeWarning
    {
        $row = $this->db->fetchOne("SELECT * FROM bike_warnings WHERE id = ?", [$id]);

        return $row ? BikeWarning::fromRow($row) : null;
    }

    /**
     * Find the active warning for a bike (if any).
     */
    public function findActiveByBikeId(int $bikeId): ?BikeWarning
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM bike_warnings WHERE bike_id = ? AND type = 'warning' AND status = 'active' LIMIT 1",
            [$bikeId]
        );

        return $row ? BikeWarning::fromRow($row) : null;
    }

    /**
     * @return BikeWarning[]
     */
    public function findTimelineByBikeId(int $bikeId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM bike_warnings WHERE bike_id = ? ORDER BY created_at DESC",
            [$bikeId]
        );

        return array_map(fn(array $row) => BikeWarning::fromRow($row), $rows);
    }

    /**
     * Check whether a bike has any active warning.
     */
    public function hasActiveWarning(int $bikeId): bool
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM bike_warnings WHERE bike_id = ? AND type = 'warning' AND status = 'active'",
            [$bikeId]
        );

        return (int) $count > 0;
    }

    /**
     * Mark all active warnings for a bike as resolved.
     */
    public function resolveActiveWarnings(int $bikeId): void
    {
        $this->db->query(
            "UPDATE bike_warnings SET status = 'resolved' WHERE bike_id = ? AND status = 'active'",
            [$bikeId]
        );
    }

    /**
     * @return BikeWarning[]
     */
    public function findAll(?string $status = null, ?string $type = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM bike_warnings";
        $params = [];
        $conditions = [];

        if ($status !== null) {
            $conditions[] = "status = ?";
            $params[] = $status;
        }

        if ($type !== null) {
            $conditions[] = "type = ?";
            $params[] = $type;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        $rows = $this->db->fetchAll($sql, $params);

        return array_map(fn(array $row) => BikeWarning::fromRow($row), $rows);
    }

    /**
     * Count warnings, optionally filtered by status.
     */
    public function countByStatus(?string $status = null): int
    {
        if ($status !== null) {
            return (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM bike_warnings WHERE status = ?",
                [$status]
            );
        }

        return (int) $this->db->fetchColumn("SELECT COUNT(*) FROM bike_warnings");
    }

    /**
     * Count warnings of a specific type.
     */
    public function countByType(string $type): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM bike_warnings WHERE type = ?",
            [$type]
        );
    }

    /**
     * Create a new warning entry. Returns the new ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('bike_warnings', $data);
    }

    /**
     * Update the status of a warning entry.
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->db->update('bike_warnings', ['status' => $status], 'id = ?', [$id]);
    }
}
