<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\Bike;
use App\Entity\FoundReport;

/**
 * Repository for the found_reports table.
 */
class FoundReportRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a found report by ID.
     */
    public function findById(int $id): ?FoundReport
    {
        $row = $this->db->fetchOne("SELECT * FROM found_reports WHERE id = ?", [$id]);

        return $row ? FoundReport::fromRow($row) : null;
    }

    /**
     * Find a found report by its conversation token.
     * This is the primary access method for anonymous finders.
     */
    public function findByConversationToken(string $token): ?FoundReport
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM found_reports WHERE conversation_token = ?",
            [$token]
        );

        return $row ? FoundReport::fromRow($row) : null;
    }

    /**
     * Find all found reports for a specific bike.
     * Used on the owner's dashboard to see who reported finding their bike.
     * 
     * @return FoundReport[]
     */
    public function findByBikeId(int $bikeId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM found_reports WHERE bike_id = ? ORDER BY created_at DESC",
            [$bikeId]
        );

        return array_map(fn(array $row) => FoundReport::fromRow($row), $rows);
    }

    /**
     * Find active (non-resolved) found reports for a bike.
     * 
     * @return FoundReport[]
     */
    public function findActiveByBikeId(int $bikeId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM found_reports WHERE bike_id = ? AND status != 'resolved' ORDER BY created_at DESC",
            [$bikeId]
        );

        return array_map(fn(array $row) => FoundReport::fromRow($row), $rows);
    }

    /**
     * Find all found reports submitted by a specific user.
     * 
     * @return FoundReport[]
     */
    public function findByReporter(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM found_reports WHERE reported_by = ? ORDER BY created_at DESC",
            [$userId]
        );

        return array_map(fn(array $row) => FoundReport::fromRow($row), $rows);
    }

    /**
     * Count active found reports for a bike (for badge display).
     */
    public function countActiveByBikeId(int $bikeId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM found_reports WHERE bike_id = ? AND status != 'resolved'",
            [$bikeId]
        );
    }

    /**
     * Count total resolved reports where the finder is a specific user.
     * Used for karma calculation.
     */
    public function countResolvedByReporter(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM found_reports WHERE reported_by = ? AND status = 'resolved'",
            [$userId]
        );
    }

    /**
     * Count total reports submitted by a specific user.
     * Used for karma calculation.
     */
    public function countByReporter(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM found_reports WHERE reported_by = ?",
            [$userId]
        );
    }

    /**
     * Create a new found report. Returns the report ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('found_reports', [
            'bike_id'              => $data['bike_id'] ?? null,
            'qr_hash_scanned'      => $data['qr_hash_scanned'] ?? null,
            'reported_by'          => $data['reported_by'] ?? null,
            'reporter_email'       => $data['reporter_email'] ?? null,
            'reporter_phone'       => $data['reporter_phone'] ?? null,
            'reporter_ip'          => $data['reporter_ip'] ?? null,
            'conversation_token'   => $data['conversation_token'],
            'found_date'           => $data['found_date'] ?? null,
            'found_location_text'  => $data['found_location_text'] ?? null,
            'found_location_lat'   => $data['found_location_lat'] ?? null,
            'found_location_lng'   => $data['found_location_lng'] ?? null,
            'description'          => $data['description'] ?? null,
        ]);
    }

    /**
     * Update a found report's status.
     */
    public function updateStatus(int $reportId, string $status): void
    {
        $this->db->update('found_reports', ['status' => $status], 'id = ?', [$reportId]);
    }
}