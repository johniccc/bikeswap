<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\ActivityLog;

/**
 * Repository for the activity_log table.
 *
 * Records and retrieves user actions for audit trail purposes.
 */
class ActivityLogRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Record a new activity log entry. Returns the new entry's ID.
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): int {
        return $this->db->insert('activity_log', [
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_value'   => $oldValue !== null ? json_encode($oldValue) : null,
            'new_value'   => $newValue !== null ? json_encode($newValue) : null,
            'ip_address'  => $ipAddress,
            'user_agent'  => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    /**
     * @return ActivityLog[]
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );

        return array_map([ActivityLog::class, 'fromRow'], $rows);
    }

    /**
     * @return ActivityLog[]
     */
    public function findAll(int $limit = 100): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );

        return array_map([ActivityLog::class, 'fromRow'], $rows);
    }

    /**
     * @return ActivityLog[]
     */
    public function findAllWithUsers(int $limit = 100): array
    {
        $rows = $this->db->fetchAll(
            "SELECT al.*, CONCAT(u.first_name, ' ', u.surname) AS user_name
             FROM activity_log al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT ?",
            [$limit]
        );

        return array_map([ActivityLog::class, 'fromRow'], $rows);
    }

    /**
     * @return ActivityLog[]
     */
    public function findByEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM activity_log WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC LIMIT ?",
            [$entityType, $entityId, $limit]
        );

        return array_map([ActivityLog::class, 'fromRow'], $rows);
    }
}
