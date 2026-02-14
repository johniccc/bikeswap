<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\Notification;

/**
 * Repository for the notifications table.
 */
class NotificationRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all notifications for a user, newest first.
     * 
     * @return Notification[]
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );

        return array_map(fn(array $row) => Notification::fromRow($row), $rows);
    }

    /**
     * Get only unread notifications for a user.
     * 
     * @return Notification[]
     */
    public function findUnreadByUserId(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC",
            [$userId]
        );

        return array_map(fn(array $row) => Notification::fromRow($row), $rows);
    }

    /**
     * Count unread notifications for a user (for badge display).
     */
    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    /**
     * Create a new notification. Returns the notification ID.
     */
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId): void
    {
        $this->db->update('notifications', ['is_read' => 1], 'id = ?', [$notificationId]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): void
    {
        $this->db->update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [$userId]);
    }

    /**
     * Delete old read notifications (cleanup, optional).
     * Keeps the last N days of read notifications.
     */
    public function deleteOldRead(int $daysToKeep = 90): int
    {
        return $this->db->delete(
            'notifications',
            'is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$daysToKeep]
        );
    }
}