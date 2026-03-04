<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\ReservationMessage;

/**
 * Repository for the reservation_messages table.
 */
class ReservationMessageRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all messages for a reservation, ordered chronologically.
     *
     * @return ReservationMessage[]
     */
    public function findByReservationId(int $reservationId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservation_messages WHERE reservation_id = ? ORDER BY created_at ASC",
            [$reservationId]
        );

        return array_map(fn(array $row) => ReservationMessage::fromRow($row), $rows);
    }

    /**
     * Create a new message. Returns the new ID.
     */
    public function create(int $reservationId, string $senderType, ?int $senderUserId, string $message): int
    {
        return $this->db->insert('reservation_messages', [
            'reservation_id' => $reservationId,
            'sender_type'    => $senderType,
            'sender_user_id' => $senderUserId,
            'message'        => $message,
        ]);
    }

    /**
     * Get all messages for a reservation with id > $afterId, ordered chronologically.
     *
     * @return ReservationMessage[]
     */
    public function findAfter(int $reservationId, int $afterId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM reservation_messages WHERE reservation_id = ? AND id > ? ORDER BY id ASC",
            [$reservationId, $afterId]
        );

        return array_map(fn(array $row) => ReservationMessage::fromRow($row), $rows);
    }

    /**
     * Mark all messages as read for a specific viewer.
     * Owner reads messages from borrower, borrower reads messages from owner.
     */
    public function markAsReadForViewer(int $reservationId, string $viewerType): void
    {
        // Owner reads borrower messages, borrower reads owner messages
        $senderType = match ($viewerType) {
            'owner'    => 'borrower',
            'borrower' => 'owner',
            default    => null,
        };

        if ($senderType === null) {
            return;
        }

        $this->db->update(
            'reservation_messages',
            ['is_read' => 1],
            'reservation_id = ? AND sender_type = ? AND is_read = 0',
            [$reservationId, $senderType]
        );
    }

    /**
     * Count unread messages for a user across all their reservations.
     */
    public function countUnreadForUser(int $userId, string $role): int
    {
        // role = 'owner' → count unread borrower messages in owner's reservations
        // role = 'borrower' → count unread owner messages in borrower's reservations
        $otherType = $role === 'owner' ? 'borrower' : 'owner';
        $column = $role === 'owner' ? 'owner_id' : 'borrower_id';

        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM reservation_messages rm
             JOIN reservations r ON r.id = rm.reservation_id
             WHERE r.{$column} = ? AND rm.sender_type = ? AND rm.is_read = 0",
            [$userId, $otherType]
        );
    }
}