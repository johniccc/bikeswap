<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\BikeWarning;

class BikeWarningRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?BikeWarning
    {
        $row = $this->db->fetchOne("SELECT * FROM bike_warnings WHERE id = ?", [$id]);

        return $row ? BikeWarning::fromRow($row) : null;
    }

    public function findActiveByBikeId(int $bikeId): ?BikeWarning
    {
        // Lazily expire overdue warnings
        $this->db->query(
            "UPDATE bike_warnings SET status = 'expired' WHERE bike_id = ? AND status = 'active' AND deadline < CURDATE()",
            [$bikeId]
        );

        $row = $this->db->fetchOne(
            "SELECT * FROM bike_warnings WHERE bike_id = ? AND status = 'active' LIMIT 1",
            [$bikeId]
        );

        return $row ? BikeWarning::fromRow($row) : null;
    }

    /**
     * @return BikeWarning[]
     */
    public function findAll(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM bike_warnings";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        $rows = $this->db->fetchAll($sql, $params);

        return array_map(fn(array $row) => BikeWarning::fromRow($row), $rows);
    }

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

    public function create(array $data): int
    {
        return $this->db->insert('bike_warnings', $data);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->update('bike_warnings', ['status' => $status], 'id = ?', [$id]);
    }
}
