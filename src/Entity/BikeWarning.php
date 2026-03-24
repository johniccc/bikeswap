<?php

declare(strict_types=1);

namespace App\Entity;

class BikeWarning
{
    private int $id;
    private int $bikeId;
    private int $createdBy;
    private ?string $reason;
    private ?string $deadline;
    private ?string $locationDescription;
    private string $type;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    private function __construct(
        int $id,
        int $bikeId,
        int $createdBy,
        ?string $reason,
        ?string $deadline,
        ?string $locationDescription,
        string $type,
        string $status,
        string $createdAt,
        string $updatedAt
    ) {
        $this->id = $id;
        $this->bikeId = $bikeId;
        $this->createdBy = $createdBy;
        $this->reason = $reason;
        $this->deadline = $deadline;
        $this->locationDescription = $locationDescription;
        $this->type = $type;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            bikeId: (int) $row['bike_id'],
            createdBy: (int) $row['created_by'],
            reason: $row['reason'] ?? null,
            deadline: $row['deadline'] ?? null,
            locationDescription: $row['location_description'] ?? null,
            type: $row['type'] ?? 'warning',
            status: $row['status'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }

    public function getId(): int { return $this->id; }
    public function getBikeId(): int { return $this->bikeId; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getReason(): ?string { return $this->reason; }
    public function getDeadline(): ?string { return $this->deadline; }
    public function getLocationDescription(): ?string { return $this->locationDescription; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isWarning(): bool
    {
        return $this->type === 'warning';
    }

    public function isOwnerPickup(): bool
    {
        return $this->type === 'owner_pickup';
    }

    public function isSeized(): bool
    {
        return $this->type === 'seized';
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active'   => 'Aktivní',
            'resolved' => 'Vyřešeno',
            default    => $this->status,
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'active'   => 'status-active',
            'resolved' => 'status-resolved',
            default    => '',
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'warning'      => 'Upozornění',
            'owner_pickup' => 'Majitel vyzvednul',
            'seized'       => 'Kolo odvezeno',
            default        => $this->type,
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'warning'      => 'alert-triangle',
            'owner_pickup' => 'check-circle',
            'seized'       => 'truck',
            default        => 'info',
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'warning'      => 'warning',
            'owner_pickup' => 'success',
            'seized'       => 'danger',
            default        => 'muted',
        };
    }

    public function getFormattedDeadline(): string
    {
        if ($this->deadline === null) {
            return '—';
        }
        return date('d.m.Y', strtotime($this->deadline));
    }
}
