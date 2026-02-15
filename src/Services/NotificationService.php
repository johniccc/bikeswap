<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\NotificationRepository;

/**
 * Notification service — thin facade over NotificationRepository.
 *
 * Does NOT contain business logic or entity knowledge.
 * Each calling service composes its own notification texts,
 * because it knows the context best.
 */
class NotificationService
{
    private NotificationRepository $repository;

    public function __construct(NotificationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a notification for a user.
     *
     * @param int         $userId  Recipient user ID
     * @param string      $type    Notification category (e.g. 'found_report', 'reservation', 'message', 'theft_resolved')
     * @param string      $title   Short headline shown in notification list
     * @param string|null $link    Optional URL to redirect when clicked
     * @param string|null $message Optional longer description (defaults to $title if omitted)
     *
     * @return int Created notification ID
     */
    public function notify(
        int $userId,
        string $type,
        string $title,
        ?string $link = null,
        ?string $message = null
    ): int {
        return $this->repository->create(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message ?? $title,
            link: $link
        );
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->repository->countUnread($userId);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $id): void
    {
        $this->repository->markAsRead($id);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): void
    {
        $this->repository->markAllAsRead($userId);
    }
}