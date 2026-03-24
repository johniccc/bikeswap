<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Immutable representation of a user's device fingerprint record.
 *
 * Stores hashed browser fingerprint data along with device attributes
 * for security monitoring and suspicious login detection.
 */
class UserDevice
{
    private int $id;
    private int $userId;
    private string $fingerprintHash;
    private ?string $ipAddress;
    private ?string $userAgent;
    private ?string $screenResolution;
    private ?string $timezone;
    private ?string $language;
    private ?string $platform;
    private ?int $colorDepth;
    private ?bool $touchSupport;
    private ?bool $doNotTrack;
    private string $firstSeenAt;
    private string $lastSeenAt;

    public function __construct(
        int $id,
        int $userId,
        string $fingerprintHash,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $screenResolution,
        ?string $timezone,
        ?string $language,
        ?string $platform,
        ?int $colorDepth,
        ?bool $touchSupport,
        ?bool $doNotTrack,
        string $firstSeenAt,
        string $lastSeenAt
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->fingerprintHash = $fingerprintHash;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->screenResolution = $screenResolution;
        $this->timezone = $timezone;
        $this->language = $language;
        $this->platform = $platform;
        $this->colorDepth = $colorDepth;
        $this->touchSupport = $touchSupport;
        $this->doNotTrack = $doNotTrack;
        $this->firstSeenAt = $firstSeenAt;
        $this->lastSeenAt = $lastSeenAt;
    }

    /**
     * Construct a UserDevice from a database row.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            fingerprintHash: $row['fingerprint_hash'],
            ipAddress: $row['ip_address'] ?? null,
            userAgent: $row['user_agent'] ?? null,
            screenResolution: $row['screen_resolution'] ?? null,
            timezone: $row['timezone'] ?? null,
            language: $row['language'] ?? null,
            platform: $row['platform'] ?? null,
            colorDepth: $row['color_depth'] !== null ? (int) $row['color_depth'] : null,
            touchSupport: $row['touch_support'] !== null ? (bool) $row['touch_support'] : null,
            doNotTrack: $row['do_not_track'] !== null ? (bool) $row['do_not_track'] : null,
            firstSeenAt: $row['first_seen_at'],
            lastSeenAt: $row['last_seen_at'],
        );
    }

    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getFingerprintHash(): string { return $this->fingerprintHash; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getScreenResolution(): ?string { return $this->screenResolution; }
    public function getTimezone(): ?string { return $this->timezone; }
    public function getLanguage(): ?string { return $this->language; }
    public function getPlatform(): ?string { return $this->platform; }
    public function getColorDepth(): ?int { return $this->colorDepth; }
    public function getTouchSupport(): ?bool { return $this->touchSupport; }
    public function getDoNotTrack(): ?bool { return $this->doNotTrack; }
    public function getFirstSeenAt(): string { return $this->firstSeenAt; }
    public function getLastSeenAt(): string { return $this->lastSeenAt; }

    /**
     * Build a human-readable summary line from platform, resolution, and language.
     */
    public function getDeviceSummary(): string
    {
        $parts = [];
        if ($this->platform) {
            $parts[] = $this->platform;
        }
        if ($this->screenResolution) {
            $parts[] = $this->screenResolution;
        }
        if ($this->language) {
            $parts[] = $this->language;
        }

        return implode(' · ', $parts) ?: '—';
    }
}
