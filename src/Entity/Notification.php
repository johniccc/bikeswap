<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Notification entity.
 * 
 * In-app notifications displayed in the dashboard.
 * Types include: found_report_new, message_new, theft_resolved,
 * reservation_new, reservation_approved, reservation_rejected, etc.
 */
class Notification
{
    private int $id;
    private int $userId;
    private string $type;
    private string $title;
    private string $message;
    private ?string $link;
    private bool $isRead;
    private string $createdAt;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $notif = new self();

        $notif->id        = (int) $row['id'];
        $notif->userId    = (int) $row['user_id'];
        $notif->type      = $row['type'];
        $notif->title     = $row['title'];
        $notif->message   = $row['message'];
        $notif->link      = $row['link'] ?? null;
        $notif->isRead    = (bool) ($row['is_read'] ?? false);
        $notif->createdAt = $row['created_at'];

        return $notif;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function getTitle(): string { return $this->title; }
    public function getMessage(): string { return $this->message; }
    public function getLink(): ?string { return $this->link; }
    public function isRead(): bool { return $this->isRead; }
    public function getCreatedAt(): string { return $this->createdAt; }

    /**
     * Get icon class based on notification type (for UI rendering).
     */
    public function getIconClass(): string
    {
        return match (true) {
            str_starts_with($this->type, 'found_report') => 'icon-found',
            str_starts_with($this->type, 'message')      => 'icon-message',
            str_starts_with($this->type, 'theft')         => 'icon-theft',
            str_starts_with($this->type, 'reservation')   => 'icon-reservation',
            default                                        => 'icon-default',
        };
    }

    /**
     * Format timestamp for display.
     */
    public function getFormattedTime(): string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->createdAt);

        if (!$date) {
            return $this->createdAt;
        }

        $now = new \DateTime();
        $diff = $now->diff($date);

        // Relative time for recent notifications
        if ($diff->days === 0) {
            if ($diff->h === 0) {
                return $diff->i <= 1 ? 'Právě teď' : "před {$diff->i} min";
            }

            return "před {$diff->h} h";
        }

        if ($diff->days === 1) {
            return 'Včera ' . $date->format('H:i');
        }

        return $date->format('j. n. Y H:i');
    }
}