<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Repository for the bike_excluded_dates table.
 *
 * Manages dates when a bike is unavailable for borrowing
 * (owner-defined blackout dates).
 */
class BikeExcludedDateRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return string[] Array of date strings ['2026-03-20', ...]
     */
    public function findByBikeId(int $bikeId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT excluded_date FROM bike_excluded_dates WHERE bike_id = ? ORDER BY excluded_date ASC",
            [$bikeId]
        );

        return array_map(fn($row) => $row['excluded_date'], $rows);
    }

    /**
     * Add a single excluded date for a bike (ignores duplicates).
     */
    public function add(int $bikeId, string $date): void
    {
        $this->db->query(
            "INSERT IGNORE INTO bike_excluded_dates (bike_id, excluded_date) VALUES (?, ?)",
            [$bikeId, $date]
        );
    }

    /**
     * Remove a single excluded date for a bike.
     */
    public function remove(int $bikeId, string $date): void
    {
        $this->db->delete('bike_excluded_dates', 'bike_id = ? AND excluded_date = ?', [$bikeId, $date]);
    }

    /**
     * Replace all excluded dates for a bike with a new set.
     *
     * @param string[] $dates Array of date strings (Y-m-d format)
     */
    public function replaceAll(int $bikeId, array $dates): void
    {
        $this->db->delete('bike_excluded_dates', 'bike_id = ?', [$bikeId]);

        foreach ($dates as $date) {
            $this->db->insert('bike_excluded_dates', [
                'bike_id'       => $bikeId,
                'excluded_date' => $date,
            ]);
        }
    }

    /**
     * Check if a specific date is excluded for a bike.
     */
    public function isExcluded(int $bikeId, string $date): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM bike_excluded_dates WHERE bike_id = ? AND excluded_date = ?",
            [$bikeId, $date]
        );

        return $row !== null;
    }

    /**
     * Check if any excluded date falls within a given date range.
     */
    public function hasExcludedInRange(int $bikeId, string $dateFrom, string $dateTo): bool
    {
        $row = $this->db->fetchOne(
            "SELECT 1 FROM bike_excluded_dates WHERE bike_id = ? AND excluded_date BETWEEN ? AND ?",
            [$bikeId, $dateFrom, $dateTo]
        );

        return $row !== null;
    }
}
