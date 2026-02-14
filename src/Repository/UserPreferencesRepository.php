<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\UserPreferences;

/**
 * Repository for the user_preferences table.
 * 
 * Uses a "lazy creation" pattern: if no row exists for a user,
 * returns defaults (all TRUE). Row is created on first save.
 */
class UserPreferencesRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get preferences for a user.
     * Returns defaults if no row exists yet.
     */
    public function findByUserId(int $userId): UserPreferences
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM user_preferences WHERE user_id = ?",
            [$userId]
        );

        if ($row === null) {
            return UserPreferences::defaults($userId);
        }

        return UserPreferences::fromRow($row);
    }

    /**
     * Save preferences for a user (INSERT or UPDATE).
     * Uses INSERT ... ON DUPLICATE KEY UPDATE for atomic upsert.
     */
    public function save(int $userId, array $preferences): void
    {
        $allowed = ['email_on_found_report', 'email_on_message', 'email_on_status_change', 'email_on_reservation'];
        $filtered = array_intersect_key($preferences, array_flip($allowed));

        // Cast all values to int (0 or 1)
        $filtered = array_map(fn($v) => $v ? 1 : 0, $filtered);

        $columns = array_merge(['user_id'], array_keys($filtered));
        $values = array_merge([$userId], array_values($filtered));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $updateParts = array_map(fn($col) => "{$col} = VALUES({$col})", array_keys($filtered));
        $updateClause = implode(', ', $updateParts);

        $columnList = implode(', ', $columns);

        $this->db->query(
            "INSERT INTO user_preferences ({$columnList}) VALUES ({$placeholders})
             ON DUPLICATE KEY UPDATE {$updateClause}",
            $values
        );
    }

    /**
     * Check if a specific preference is enabled for a user.
     * Used by EmailService to decide whether to send.
     */
    public function isEnabled(int $userId, string $preference): bool
    {
        $prefs = $this->findByUserId($userId);

        return match ($preference) {
            'email_on_found_report'  => $prefs->isEmailOnFoundReport(),
            'email_on_message'       => $prefs->isEmailOnMessage(),
            'email_on_status_change' => $prefs->isEmailOnStatusChange(),
            'email_on_reservation'   => $prefs->isEmailOnReservation(),
            default                  => true,
        };
    }
}