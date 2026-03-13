<?php

declare(strict_types=1);

namespace App\Entity;

class BikeWarning
{
    private int $id;
    private int $bikeId;
    private int $createdBy;
    private string $reason;
    private string $deadline;
    private string $locationDescription;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    private function __construct(
        int $id,
        int $bikeId,
        int $createdBy,
        string $reason,
        string $deadline,
        string $locationDescription,
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
            reason: $row['reason'],
            deadline: $row['deadline'],
            locationDescription: $row['location_description'],
            status: $row['status'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }

    public function getId(): int { return $this->id; }
    public function getBikeId(): int { return $this->bikeId; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getReason(): string { return $this->reason; }
    public function getDeadline(): string { return $this->deadline; }
    public function getLocationDescription(): string { return $this->locationDescription; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active'   => 'Aktivní',
            'resolved' => 'Vyřešeno',
            'expired'  => 'Vypršelo',
            default    => $this->status,
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'active'   => 'status-active',
            'resolved' => 'status-resolved',
            'expired'  => 'status-expired',
            default    => '',
        };
    }

    public function getFormattedDeadline(): string
    {
        return date('d.m.Y', strtotime($this->deadline));
    }
}
