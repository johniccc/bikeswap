<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Notification entity.
 *
 * Represents an in-app notification for a user.
 * Types: found_report, message, theft_resolved, system
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

    public function __construct(
        int $id,
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link,
        bool $isRead,
        string $createdAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
        $this->isRead = $isRead;
        $this->createdAt = $createdAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            type: $row['type'],
            title: $row['title'],
            message: $row['message'],
            link: $row['link'] ?? null,
            isRead: (bool) ($row['is_read'] ?? false),
            createdAt: $row['created_at'],
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Whether this notification requires user action (shown in "Akce" section).
     */
    public function isAction(): bool
    {
        return in_array($this->type, ['warning', 'bike_warning'], true);
    }

    /**
     * CSS class for the notification icon based on type.
     */
    public function getIconClass(): string
    {
        if ($this->isAction()) {
            return 'icon-action';
        }

        return match ($this->type) {
            'found_report'   => 'icon-found',
            'message'        => 'icon-message',
            'theft_resolved' => 'icon-resolved',
            default          => 'icon-info',
        };
    }

    /**
     * Relative time formatting in Czech.
     * "Právě teď", "před 5 min", "Včera 14:30", "12. 1. 2026"
     */
    public function getFormattedTime(): string
    {
        $timestamp = strtotime($this->createdAt);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'Právě teď';
        }

        if ($diff < 3600) {
            $minutes = (int) floor($diff / 60);
            return "před {$minutes} min";
        }

        if ($diff < 86400 && date('d', $timestamp) === date('d', $now)) {
            return 'Dnes ' . date('H:i', $timestamp);
        }

        if ($diff < 172800 && date('d', $timestamp) === date('d', $now - 86400)) {
            return 'Včera ' . date('H:i', $timestamp);
        }

        return date('d/m/Y', $timestamp);
    }
}