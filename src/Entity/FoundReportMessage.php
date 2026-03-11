<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FoundReportMessage entity.
 * 
 * Represents a single message in the anonymous conversation
 * between a bike owner and the person who found the bike.
 * 
 * sender_type distinguishes who sent the message:
 *   - 'owner'  — the bike owner (always a registered user)
 *   - 'finder' — the person who found the bike (may or may not be registered)
 *   - 'system' — automated messages (e.g. "Majitel označil kolo jako nalezené")
 */
class FoundReportMessage
{
    private int $id;
    private int $foundReportId;
    private string $senderType;
    private ?int $senderUserId;
    private string $message;
    private bool $isRead;
    private string $createdAt;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $msg = new self();

        $msg->id             = (int) $row['id'];
        $msg->foundReportId  = (int) $row['found_report_id'];
        $msg->senderType     = $row['sender_type'];
        $msg->senderUserId   = isset($row['sender_user_id']) ? (int) $row['sender_user_id'] : null;
        $msg->message        = $row['message'];
        $msg->isRead         = (bool) ($row['is_read'] ?? false);
        $msg->createdAt      = $row['created_at'];

        return $msg;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getFoundReportId(): int { return $this->foundReportId; }
    public function getSenderType(): string { return $this->senderType; }
    public function getSenderUserId(): ?int { return $this->senderUserId; }
    public function getMessage(): string { return $this->message; }
    public function isRead(): bool { return $this->isRead; }
    public function getCreatedAt(): string { return $this->createdAt; }

    // ── Sender type checks ─────────────────────────────────────

    public function isFromOwner(): bool { return $this->senderType === 'owner'; }
    public function isFromFinder(): bool { return $this->senderType === 'finder'; }
    public function isSystemMessage(): bool { return $this->senderType === 'system'; }

    /**
     * Get a display label for the sender (used in conversation UI).
     * No real names are ever shown — this is intentionally anonymous.
     */
    public function getSenderLabel(): string
    {
        return match ($this->senderType) {
            'system' => 'BikeSwap',
            'owner'  => 'Vlastník',
            'finder' => 'Nálezce',
            'admin'  => 'Administrátor',
            default  => 'Neznámý',
        };
    }

    /**
     * Get CSS class for message bubble styling.
     */
    public function getSenderClass(): string
    {
        return match ($this->senderType) {
            'owner'  => 'message-owner',
            'finder' => 'message-finder',
            'system' => 'message-system',
            'admin'  => 'message-admin',
            default  => 'message-unknown',
        };
    }

    /**
     * Format timestamp for display.
     */
    public function getFormattedTime(): string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->createdAt);

        return $date ? $date->format('d/m/Y H:i') : $this->createdAt;
    }
}