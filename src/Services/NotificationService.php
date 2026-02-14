<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Bike;
use App\Repository\NotificationRepository;

/**
 * Notification service.
 * 
 * Creates in-app notifications for users.
 * Each method represents a specific event type.
 */
class NotificationService
{
    private NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Notify bike owner that someone reported finding their bike.
     */
    public function notifyFoundReport(int $ownerId, Bike $bike, int $reportId): void
    {
        $this->notificationRepository->create(
            $ownerId,
            'found_report_new',
            'Někdo nalezl vaše kolo!',
            sprintf('Někdo nahlásil nález vašeho kola %s. Podívejte se na detail a kontaktujte nálezce.', $bike->getFullName()),
            '/bike/' . $bike->getQrHash()
        );
    }

    /**
     * Notify bike owner about a new message in a found report conversation.
     */
    public function notifyNewMessage(int $ownerId, Bike $bike, int $reportId): void
    {
        $this->notificationRepository->create(
            $ownerId,
            'message_new',
            'Nová zpráva od nálezce',
            sprintf('Máte novou zprávu v konverzaci o kole %s.', $bike->getFullName()),
            '/bike/' . $bike->getQrHash()
        );
    }

    /**
     * Notify bike owner that a theft report was resolved.
     */
    public function notifyTheftResolved(int $ownerId, Bike $bike): void
    {
        $this->notificationRepository->create(
            $ownerId,
            'theft_resolved',
            'Kolo označeno jako nalezené',
            sprintf('Vaše kolo %s bylo úspěšně označeno jako nalezené.', $bike->getFullName()),
            '/bike/' . $bike->getQrHash()
        );
    }

    /**
     * Get the unread notification count for a user (for badge).
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->countUnread($userId);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId): void
    {
        $this->notificationRepository->markAsRead($notificationId);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): void
    {
        $this->notificationRepository->markAllAsRead($userId);
    }
}