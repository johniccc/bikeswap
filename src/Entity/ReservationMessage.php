<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * ReservationMessage entity.
 *
 * A single message within a reservation conversation.
 * Follows the same pattern as FoundReportMessage.
 */
class ReservationMessage
{
    private int $id;
    private int $reservationId;
    private string $senderType; // owner, borrower, system
    private ?int $senderUserId;
    private string $message;
    private bool $isRead;
    private string $createdAt;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $m = new self();

        $m->id             = (int) $row['id'];
        $m->reservationId  = (int) $row['reservation_id'];
        $m->senderType     = $row['sender_type'];
        $m->senderUserId   = isset($row['sender_user_id']) ? (int) $row['sender_user_id'] : null;
        $m->message        = $row['message'];
        $m->isRead         = (bool) ($row['is_read'] ?? false);
        $m->createdAt      = $row['created_at'];

        return $m;
    }

    // ── Getters ────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getReservationId(): int { return $this->reservationId; }
    public function getSenderType(): string { return $this->senderType; }
    public function getSenderUserId(): ?int { return $this->senderUserId; }
    public function getMessage(): string { return $this->message; }
    public function isRead(): bool { return $this->isRead; }
    public function getCreatedAt(): string { return $this->createdAt; }

    // ── Presentation ───────────────────────────────────────

    public function isSystemMessage(): bool
    {
        return $this->senderType === 'system';
    }

    public function getSenderLabel(): string
    {
        return match ($this->senderType) {
            'owner'    => 'Majitel',
            'borrower' => 'Vypůjčitel',
            'system'   => 'Systém',
            default    => 'Neznámý',
        };
    }

    public function getSenderClass(): string
    {
        return match ($this->senderType) {
            'owner'    => 'message-owner',
            'borrower' => 'message-finder',  // reuse existing CSS class
            'system'   => 'message-system',
            default    => '',
        };
    }

    public function getFormattedTime(): string
    {
        return date('d/m/Y H:i', strtotime($this->createdAt));
    }
}