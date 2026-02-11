<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Bike entity.
 * 
 * Represents a registered bicycle in the system.
 * Immutable — created from database row via static factory.
 */
class Bike
{
    private int $id;
    private int $ownerId;
    private string $qrHash;
    private string $brand;
    private ?string $model;
    private string $color;
    private ?string $frameNumber;
    private ?int $yearOfManufacture;
    private ?string $description;
    private string $status;
    private bool $isShared;
    private string $createdAt;
    private string $updatedAt;

    /** @var BikePhoto[] Loaded separately, not always present */
    private array $photos = [];

    /** @var User|null Owner entity, loaded separately */
    private ?User $owner = null;

    private function __construct() {}

    /**
     * Create a Bike entity from a database row.
     */
    public static function fromRow(array $row): self
    {
        $bike = new self();

        $bike->id                = (int) $row['id'];
        $bike->ownerId           = (int) $row['owner_id'];
        $bike->qrHash            = $row['qr_hash'];
        $bike->brand             = $row['brand'];
        $bike->model             = $row['model'] ?? null;
        $bike->color             = $row['color'];
        $bike->frameNumber       = $row['frame_number'] ?? null;
        $bike->yearOfManufacture = isset($row['year_of_manufacture']) ? (int) $row['year_of_manufacture'] : null;
        $bike->description       = $row['description'] ?? null;
        $bike->status            = $row['status'];
        $bike->isShared          = (bool) ($row['is_shared'] ?? false);
        $bike->createdAt         = $row['created_at'];
        $bike->updatedAt         = $row['updated_at'];

        return $bike;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getQrHash(): string
    {
        return $this->qrHash;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getFrameNumber(): ?string
    {
        return $this->frameNumber;
    }

    public function getYearOfManufacture(): ?int
    {
        return $this->yearOfManufacture;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isShared(): bool
    {
        return $this->isShared;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Get human-readable status in Czech.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active'    => 'Aktivní',
            'stolen'    => 'Odcizené',
            'found'     => 'Nalezené',
            'recovered' => 'Navrácené',
            default     => 'Neznámý',
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function getStatusClass(): string
    {
        return match ($this->status) {
            'active'    => 'status-active',
            'stolen'    => 'status-stolen',
            'found'     => 'status-found',
            'recovered' => 'status-recovered',
            default     => 'status-unknown',
        };
    }

    // ── Status checks ──────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isStolen(): bool
    {
        return $this->status === 'stolen';
    }

    public function isFound(): bool
    {
        return $this->status === 'found';
    }

    public function isRecovered(): bool
    {
        return $this->status === 'recovered';
    }

    // ── Relations (set externally) ─────────────────────────────

    /**
     * @param BikePhoto[] $photos
     */
    public function setPhotos(array $photos): void
    {
        $this->photos = $photos;
    }

    /**
     * @return BikePhoto[]
     */
    public function getPhotos(): array
    {
        return $this->photos;
    }

    /**
     * Get the primary photo, or the first one, or null.
     */
    public function getPrimaryPhoto(): ?BikePhoto
    {
        foreach ($this->photos as $photo) {
            if ($photo->isPrimary()) {
                return $photo;
            }
        }

        return $this->photos[0] ?? null;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * Check if a given user is the owner of this bike.
     */
    public function isOwnedBy(int $userId): bool
    {
        return $this->ownerId === $userId;
    }

    /**
     * Get full display name (brand + model).
     */
    public function getFullName(): string
    {
        if ($this->model !== null && $this->model !== '') {
            return $this->brand . ' ' . $this->model;
        }

        return $this->brand;
    }
}