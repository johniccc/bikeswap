<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class DisputePhotoRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(int $reservationId, string $filePath): int
    {
        return $this->db->insert('dispute_photos', [
            'reservation_id' => $reservationId,
            'file_path'      => $filePath,
        ]);
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM dispute_photos WHERE id = ?",
            [$id]
        );
        return $row ?: null;
    }

    /**
     * @return array<array{id: int, reservation_id: int, file_path: string, uploaded_at: string}>
     */
    public function findByReservation(int $reservationId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM dispute_photos WHERE reservation_id = ? ORDER BY uploaded_at ASC",
            [$reservationId]
        );
    }
}
