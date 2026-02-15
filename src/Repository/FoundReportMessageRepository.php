<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\FoundReportMessage;

/**
 * Repository for the found_report_messages table.
 * 
 * Separated from FoundReportRepository because messages have
 * their own complex operations (read/unread tracking, counting).
 */
class FoundReportMessageRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all messages for a found report, ordered chronologically.
     * 
     * @return FoundReportMessage[]
     */
    public function findByReportId(int $reportId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM found_report_messages WHERE found_report_id = ? ORDER BY created_at ASC",
            [$reportId]
        );

        return array_map(fn(array $row) => FoundReportMessage::fromRow($row), $rows);
    }

    /**
     * Create a new message. Returns the message ID.
     */
    public function create(int $reportId, string $senderType, ?int $senderUserId, string $message): int
    {
        return $this->db->insert('found_report_messages', [
            'found_report_id' => $reportId,
            'sender_type'     => $senderType,
            'sender_user_id'  => $senderUserId,
            'message'         => $message,
        ]);
    }

    /**
     * Mark all messages in a report as read for a given viewer.
     * 
     * The viewer sees messages from the OTHER side as unread.
     * Owner marks finder+system messages as read.
     * Finder marks owner+system messages as read.
     */
    public function markAsReadForViewer(int $reportId, string $viewerType): void
    {
        // Owner reads messages from finder and system
        // Finder reads messages from owner and system
        $otherTypes = match ($viewerType) {
            'owner'  => ['finder', 'system'],
            'finder' => ['owner', 'system'],
            default  => [],
        };

        if (empty($otherTypes)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($otherTypes), '?'));
        $params = array_merge([$reportId], $otherTypes);

        // Note: Using query() instead of update() because we need IN clause support
        $this->db->query(
            "UPDATE found_report_messages SET is_read = 1
             WHERE found_report_id = ? AND sender_type IN ({$placeholders}) AND is_read = 0",
            $params
        );
    }

    /**
     * Count unread messages in a report for a specific viewer.
     */
    public function countUnreadForViewer(int $reportId, string $viewerType): int
    {
        $otherTypes = match ($viewerType) {
            'owner'  => ['finder', 'system'],
            'finder' => ['owner', 'system'],
            default  => [],
        };

        if (empty($otherTypes)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($otherTypes), '?'));
        $params = array_merge([$reportId], $otherTypes);

        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM found_report_messages 
             WHERE found_report_id = ? AND sender_type IN ({$placeholders}) AND is_read = 0",
            $params
        );
    }

    /**
     * Count total unread messages across all found reports for bikes owned by a user.
     * Used for the dashboard badge.
     */
    public function countUnreadForOwner(int $ownerUserId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM found_report_messages frm
             JOIN found_reports fr ON frm.found_report_id = fr.id
             JOIN bikes b ON fr.bike_id = b.id
             WHERE b.owner_id = ? AND frm.sender_type IN ('finder', 'system') AND frm.is_read = 0",
            [$ownerUserId]
        );
    }
}