<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FoundReport entity.
 * 
 * Represents a report from someone who found a (possibly stolen) bike.
 * Each report has a unique conversation_token that allows the finder
 * to access the anonymous conversation without being logged in.
 */
class FoundReport
{
    private int $id;
    private ?int $bikeId;
    private ?string $qrHashScanned;
    private ?int $reportedBy;
    private ?string $reporterEmail;
    private ?string $reporterPhone;
    private ?string $reporterIp;
    private string $conversationToken;
    private ?string $foundDate;
    private ?string $foundLocationText;
    private ?float $foundLocationLat;
    private ?float $foundLocationLng;
    private ?string $description;
    private string $status;
    private string $createdAt;

    /** @var Bike|null Loaded separately */
    private ?Bike $bike = null;

    /** @var FoundReportMessage[] Loaded separately */
    private array $messages = [];

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $report = new self();

        $report->id                = (int) $row['id'];
        $report->bikeId            = isset($row['bike_id']) ? (int) $row['bike_id'] : null;
        $report->qrHashScanned     = $row['qr_hash_scanned'] ?? null;
        $report->reportedBy        = isset($row['reported_by']) ? (int) $row['reported_by'] : null;
        $report->reporterEmail     = $row['reporter_email'] ?? null;
        $report->reporterPhone     = $row['reporter_phone'] ?? null;
        $report->reporterIp        = $row['reporter_ip'] ?? null;
        $report->conversationToken = $row['conversation_token'];
        $report->foundDate         = $row['found_date'] ?? null;
        $report->foundLocationText = $row['found_location_text'] ?? null;
        $report->foundLocationLat  = isset($row['found_location_lat']) ? (float) $row['found_location_lat'] : null;
        $report->foundLocationLng  = isset($row['found_location_lng']) ? (float) $row['found_location_lng'] : null;
        $report->description       = $row['description'] ?? null;
        $report->status            = $row['status'];
        $report->createdAt         = $row['created_at'];

        return $report;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getId(): int { return $this->id; }
    public function getBikeId(): ?int { return $this->bikeId; }
    public function getQrHashScanned(): ?string { return $this->qrHashScanned; }
    public function getReportedBy(): ?int { return $this->reportedBy; }
    public function getReporterEmail(): ?string { return $this->reporterEmail; }
    public function getReporterPhone(): ?string { return $this->reporterPhone; }
    public function getReporterIp(): ?string { return $this->reporterIp; }
    public function getConversationToken(): string { return $this->conversationToken; }
    public function getFoundDate(): ?string { return $this->foundDate; }
    public function getFoundLocationText(): ?string { return $this->foundLocationText; }
    public function getFoundLocationLat(): ?float { return $this->foundLocationLat; }
    public function getFoundLocationLng(): ?float { return $this->foundLocationLng; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Čeká na reakci',
            'contacted' => 'Probíhá komunikace',
            'resolved'  => 'Vyřešeno',
            default     => 'Neznámý',
        };
    }

    public function getStatusClass(): string
    {
        return match ($this->status) {
            'pending'   => 'status-found',
            'contacted' => 'status-active',
            'resolved'  => 'status-recovered',
            default     => 'status-unknown',
        };
    }

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isContacted(): bool { return $this->status === 'contacted'; }
    public function isResolved(): bool { return $this->status === 'resolved'; }

    /**
     * Check if the finder was a registered user.
     */
    public function isFinderRegistered(): bool
    {
        return $this->reportedBy !== null;
    }

    /**
     * Format found date for display.
     */
    public function getFormattedFoundDate(): string
    {
        if ($this->foundDate === null) {
            return 'Neznámé datum';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $this->foundDate);

        return $date ? $date->format('j. n. Y') : $this->foundDate;
    }

    /**
     * Check if the report has GPS coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->foundLocationLat !== null && $this->foundLocationLng !== null;
    }

    // ── Relations ──────────────────────────────────────────────

    public function setBike(?Bike $bike): void { $this->bike = $bike; }
    public function getBike(): ?Bike { return $this->bike; }

    /** @param FoundReportMessage[] $messages */
    public function setMessages(array $messages): void { $this->messages = $messages; }
    /** @return FoundReportMessage[] */
    public function getMessages(): array { return $this->messages; }
}