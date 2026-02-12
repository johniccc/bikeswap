<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\TheftReport;
use App\Entity\Bike;

/**
 * Repository for the theft_reports table.
 */
class TheftReportRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a theft report by ID.
     */
    public function findById(int $id): ?TheftReport
    {
        $row = $this->db->fetchOne("SELECT * FROM theft_reports WHERE id = ?", [$id]);

        return $row ? TheftReport::fromRow($row) : null;
    }

    /**
     * Find theft reports for a specific bike.
     * 
     * @return TheftReport[]
     */
    public function findByBikeId(int $bikeId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM theft_reports WHERE bike_id = ? ORDER BY created_at DESC",
            [$bikeId]
        );

        return array_map(fn(array $row) => TheftReport::fromRow($row), $rows);
    }

    /**
     * Find the active (open/investigating) theft report for a bike.
     */
    public function findActiveByBikeId(int $bikeId): ?TheftReport
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM theft_reports WHERE bike_id = ? AND status IN ('open', 'investigating') ORDER BY created_at DESC LIMIT 1",
            [$bikeId]
        );

        return $row ? TheftReport::fromRow($row) : null;
    }

    /**
     * Get all theft reports, optionally filtered.
     * Returns reports with their associated bike data joined.
     * 
     * @return TheftReport[]
     */
    public function findAll(array $filters = []): array
    {
        $sql = "SELECT tr.*, b.brand, b.model, b.color, b.qr_hash 
                FROM theft_reports tr 
                JOIN bikes b ON tr.bike_id = b.id";
        $params = [];
        $where = [];

        if (!empty($filters['status'])) {
            $where[] = "tr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['reported_by'])) {
            $where[] = "tr.reported_by = ?";
            $params[] = $filters['reported_by'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY tr.created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);

        return array_map(function (array $row) {
            $report = TheftReport::fromRow($row);

            // Attach minimal bike info from the JOIN
            if (isset($row['brand'])) {
                $bike = Bike::fromRow([
                    'id' => $row['bike_id'],
                    'owner_id' => 0, // Not needed here
                    'qr_hash' => $row['qr_hash'],
                    'brand' => $row['brand'],
                    'model' => $row['model'] ?? null,
                    'color' => $row['color'],
                    'status' => 'stolen',
                    'is_shared' => 0,
                    'created_at' => '',
                    'updated_at' => '',
                ]);
                $report->setBike($bike);
            }

            return $report;
        }, $rows);
    }

    /**
     * Create a new theft report. Returns the report ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('theft_reports', [
            'bike_id'             => $data['bike_id'],
            'reported_by'         => $data['reported_by'] ?? null,
            'reporter_email'      => $data['reporter_email'] ?? null,
            'reporter_ip'         => $data['reporter_ip'] ?? null,
            'theft_date'          => $data['theft_date'] ?? null,
            'theft_location_text' => $data['theft_location_text'] ?? null,
            'theft_location_lat'  => $data['theft_location_lat'] ?? null,
            'theft_location_lng'  => $data['theft_location_lng'] ?? null,
            'description'         => $data['description'] ?? null,
            'police_case_number'  => $data['police_case_number'] ?? null,
        ]);
    }

    /**
     * Update a theft report's status.
     */
    public function updateStatus(int $reportId, string $status): void
    {
        $this->db->update('theft_reports', ['status' => $status], 'id = ?', [$reportId]);
    }

    /**
     * Count open theft reports (for admin dashboard).
     */
    public function countOpen(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM theft_reports WHERE status IN ('open', 'investigating')"
        );
    }
}