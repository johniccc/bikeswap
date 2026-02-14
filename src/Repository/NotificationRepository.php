<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\Notification;

/**
 * Repository for notifications table.
 */
class NotificationRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find all notifications for a user, newest first.
     *
     * @return Notification[]
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );

        return array_map([Notification::class, 'fromRow'], $rows);
    }

    /**
     * Find unread notifications for a user.
     *
     * @return Notification[]
     */
    public function findUnreadByUserId(int $userId, int $limit = 20): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );

        return array_map([Notification::class, 'fromRow'], $rows);
    }

    /**
     * Count unread notifications for a user.
     */
    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
    }

    /**
     * Find a single notification by ID.
     */
    public function findById(int $id): ?Notification
    {
        $row = $this->db->fetch(
            'SELECT * FROM notifications WHERE id = ?',
            [$id]
        );

        return $row ? Notification::fromRow($row) : null;
    }

    /**
     * Create a new notification.
     */
    public function create(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        $this->db->execute(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)',
            [$userId, $type, $title, $message, $link]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $id): void
    {
        $this->db->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ?',
            [$id]
        );
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): void
    {
        $this->db->execute(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
    }

    /**
     * Delete old read notifications (cleanup).
     */
    public function deleteOldRead(int $olderThanDays = 90): int
    {
        return $this->db->execute(
            'DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$olderThanDays]
        );
    }
}