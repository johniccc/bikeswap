<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * TheftReport entity.
 * 
 * Represents a theft report filed by a bike owner.
 * When created, the associated bike's status changes to 'stolen'.
 */
class TheftReport
{
    private int $id;
    private int $bikeId;
    private ?int $reportedBy;
    private ?string $reporterEmail;
    private ?string $reporterIp;
    private ?string $theftDate;
    private ?string $theftLocationText;
    private ?float $theftLocationLat;
    private ?float $theftLocationLng;
    private ?string $description;
    private ?string $policeCaseNumber;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    /** @var Bike|null Loaded separately */
    private ?Bike $bike = null;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $report = new self();

        $report->id                = (int) $row['id'];
        $report->bikeId            = (int) $row['bike_id'];
        $report->reportedBy        = isset($row['reported_by']) ? (int) $row['reported_by'] : null;
        $report->reporterEmail     = $row['reporter_email'] ?? null;
        $report->reporterIp        = $row['reporter_ip'] ?? null;
        $report->theftDate         = $row['theft_date'] ?? null;
        $report->theftLocationText = $row['theft_location_text'] ?? null;
        $report->theftLocationLat  = isset($row['theft_location_lat']) ? (float) $row['theft_location_lat'] : null;
        $report->theftLocationLng  = isset($row['theft_location_lng']) ? (float) $row['theft_location_lng'] : null;
        $report->description       = $row['description'] ?? null;
        $report->policeCaseNumber  = $row['police_case_number'] ?? null;
        $report->status            = $row['status'];
        $report->createdAt         = $row['created_at'];
        $report->updatedAt         = $row['updated_at'];

        return $report;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getBikeId(): int { return $this->bikeId; }
    public function getReportedBy(): ?int { return $this->reportedBy; }
    public function getReporterEmail(): ?string { return $this->reporterEmail; }
    public function getReporterIp(): ?string { return $this->reporterIp; }
    public function getTheftDate(): ?string { return $this->theftDate; }
    public function getTheftLocationText(): ?string { return $this->theftLocationText; }
    public function getTheftLocationLat(): ?float { return $this->theftLocationLat; }
    public function getTheftLocationLng(): ?float { return $this->theftLocationLng; }
    public function getDescription(): ?string { return $this->description; }
    public function getPoliceCaseNumber(): ?string { return $this->policeCaseNumber; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'open'          => 'Otevřené',
            'investigating' => 'Vyšetřování',
            'resolved'      => 'Vyřešené',
            'closed'        => 'Uzavřené',
            default         => 'Neznámý',
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'open'          => 'status-stolen',
            'investigating' => 'status-found',
            'resolved'      => 'status-active',
            'closed'        => 'status-unknown',
            default         => 'status-unknown',
        };
    }

    public function isOpen(): bool { return $this->status === 'open'; }
    public function isResolved(): bool { return $this->status === 'resolved'; }

    // ── Relations ──────────────────────────────────────────────

    public function setBike(?Bike $bike): void { $this->bike = $bike; }
    public function getBike(): ?Bike { return $this->bike; }

    /**
     * Format theft date for display.
     */
    public function getFormattedTheftDate(): string
    {
        if ($this->theftDate === null) {
            return 'Neznámé datum';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $this->theftDate);

        return $date ? $date->format('j. n. Y') : $this->theftDate;
    }

    /**
     * Check if the report has GPS coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->theftLocationLat !== null && $this->theftLocationLng !== null;
    }
}