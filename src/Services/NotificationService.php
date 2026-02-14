<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Bike;
use App\Repository\NotificationRepository;

/**
 * Notification service — facade over NotificationRepository.
 *
 * Creates typed notifications with appropriate titles, messages, and links.
 */
class NotificationService
{
    private NotificationRepository $repository;

    public function __construct(NotificationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Notify bike owner that someone reported finding their bike.
     */
    public function notifyFoundReport(int $ownerId, Bike $bike, int $reportId): void
    {
        $this->repository->create(
            userId: $ownerId,
            type: 'found_report',
            title: 'Někdo našel vaše kolo!',
            message: sprintf(
                'Bylo nahlášeno nalezení kola %s. Zkontrolujte detaily a odpovězte nálezci.',
                $bike->getFullName()
            ),
            link: "/found/{$reportId}/conversation"
        );
    }

    /**
     * Notify bike owner about a new message in found report conversation.
     */
    public function notifyNewMessage(int $ownerId, Bike $bike, int $reportId): void
    {
        $this->repository->create(
            userId: $ownerId,
            type: 'message',
            title: 'Nová zpráva od nálezce',
            message: sprintf(
                'Máte novou zprávu v konverzaci o kole %s.',
                $bike->getFullName()
            ),
            link: "/found/{$reportId}/conversation"
        );
    }

    /**
     * Notify bike owner that their theft report was resolved (bike found).
     */
    public function notifyTheftResolved(int $ownerId, Bike $bike): void
    {
        $this->repository->create(
            userId: $ownerId,
            type: 'theft_resolved',
            title: 'Kolo označeno jako nalezené',
            message: sprintf(
                'Kolo %s bylo úspěšně navráceno. Hlášení krádeže bylo uzavřeno.',
                $bike->getFullName()
            ),
            link: '/bike/' . $bike->getQrHash()
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