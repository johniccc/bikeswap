<?php

declare(strict_types=1);

namespace App\Entity;

class ActivityLog
{
    private int $id;
    private ?int $userId;
    private string $action;
    private string $entityType;
    private ?int $entityId;
    private ?array $oldValue;
    private ?array $newValue;
    private ?string $ipAddress;
    private ?string $userAgent;
    private string $createdAt;
    private ?string $userName;

    public function __construct(
        int $id,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldValue,
        ?array $newValue,
        ?string $ipAddress,
        ?string $userAgent,
        string $createdAt,
        ?string $userName = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->action = $action;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->createdAt = $createdAt;
        $this->userName = $userName;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: $row['user_id'] !== null ? (int) $row['user_id'] : null,
            action: $row['action'],
            entityType: $row['entity_type'],
            entityId: $row['entity_id'] !== null ? (int) $row['entity_id'] : null,
            oldValue: $row['old_value'] !== null ? json_decode($row['old_value'], true) : null,
            newValue: $row['new_value'] !== null ? json_decode($row['new_value'], true) : null,
            ipAddress: $row['ip_address'] ?? null,
            userAgent: $row['user_agent'] ?? null,
            createdAt: $row['created_at'],
            userName: $row['user_name'] ?? null,
        );
    }

    public function getId(): int { return $this->id; }
    public function getUserId(): ?int { return $this->userId; }
    public function getAction(): string { return $this->action; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): ?int { return $this->entityId; }
    public function getOldValue(): ?array { return $this->oldValue; }
    public function getNewValue(): ?array { return $this->newValue; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUserName(): ?string { return $this->userName; }

    public function getActionLabel(): string
    {
        return match ($this->action) {
            'login'             => 'Přihlášení',
            'login_failed'     => 'Neúspěšné přihlášení',
            'register'          => 'Registrace',
            'logout'            => 'Odhlášení',
            'profile_update'    => 'Úprava profilu',
            'password_change'   => 'Změna hesla',
            'email_change'      => 'Změna e-mailu',
            'admin_ban'         => 'Zablokování uživatele',
            'admin_unban'       => 'Odblokování uživatele',
            'admin_role_change' => 'Změna role',
            'admin_user_delete' => 'Smazání uživatele',
            'admin_bike_create' => 'Vytvoření kola (admin)',
            'admin_bike_update' => 'Úprava kola (admin)',
            'admin_bike_delete' => 'Smazání kola (admin)',
            default             => $this->action,
        };
    }

    public function getShortUserAgent(): string
    {
        if ($this->userAgent === null) {
            return '—';
        }

        $ua = $this->userAgent;
        if (strlen($ua) > 80) {
            $ua = substr($ua, 0, 80) . '…';
        }

        return $ua;
    }
}
