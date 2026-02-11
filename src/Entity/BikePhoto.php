<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * BikePhoto entity.
 * 
 * Represents a photo attached to a bike.
 */
class BikePhoto
{
    private int $id;
    private int $bikeId;
    private string $filePath;
    private bool $isPrimary;
    private string $uploadedAt;
    private int $uploadedBy;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $photo = new self();

        $photo->id         = (int) $row['id'];
        $photo->bikeId     = (int) $row['bike_id'];
        $photo->filePath   = $row['file_path'];
        $photo->isPrimary  = (bool) ($row['is_primary'] ?? false);
        $photo->uploadedAt = $row['uploaded_at'];
        $photo->uploadedBy = (int) $row['uploaded_by'];

        return $photo;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBikeId(): int
    {
        return $this->bikeId;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function getUploadedAt(): string
    {
        return $this->uploadedAt;
    }

    public function getUploadedBy(): int
    {
        return $this->uploadedBy;
    }

    /**
     * Get the URL to serve this photo through FileController.
     */
    public function getUrl(): string
    {
        return '/file/bike-photo/' . $this->id;
    }
}