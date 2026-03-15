<?php

declare(strict_types=1);

namespace App\Entity;

use App\Services\KarmaService;

/**
 * User entity.
 * 
 * Immutable representation of a row in the users table.
 * Created from database data via static factory method.
 */
class User
{
    private int $id;
    private string $email;
    private string $passwordHash;
    private string $role;
    private string $firstName;
    private string $surname;
    private ?string $phone;
    private ?string $address;
    private bool $isVerified;
    private ?string $verificationToken;
    private ?string $totpSecret;
    private bool $totpEnabled;
    private int $karmaScore;
    private bool $isBanned;
    private string $createdAt;
    private string $updatedAt;
    private ?string $lastLoginAt;

    private function __construct() {}

    /**
     * Create a User entity from a database row (assoc array).
     */
    public static function fromRow(array $row): self
    {
        $user = new self();

        $user->id                = (int) $row['id'];
        $user->email             = $row['email'];
        $user->passwordHash      = $row['password_hash'];
        $user->role              = $row['role'];
        $user->firstName         = $row['first_name'];
        $user->surname           = $row['surname'] ?? '';
        $user->phone             = $row['phone'] ?? null;
        $user->address           = $row['address'] ?? null;
        $user->isVerified        = (bool) ($row['is_verified'] ?? false);
        $user->verificationToken = $row['verification_token'] ?? null;
        $user->totpSecret        = $row['totp_secret'] ?? null;
        $user->totpEnabled       = (bool) ($row['totp_enabled'] ?? false);
        $user->karmaScore        = (int) ($row['karma_score'] ?? 0);
        $user->isBanned          = (bool) ($row['is_banned'] ?? false);
        $user->createdAt         = $row['created_at'];
        $user->updatedAt         = $row['updated_at'];
        $user->lastLoginAt       = $row['last_login_at'] ?? null;

        return $user;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->surname);
    }

    /** @deprecated Use getFullName() — kept for backwards compatibility with templates */
    public function getName(): string
    {
        return $this->getFullName();
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled;
    }

    public function getKarmaScore(): int
    {
        return $this->karmaScore;
    }

    /**
     * Get the karma level label based on cached score.
     */
    public function getKarmaLevel(): string
    {
        return KarmaService::getLevel($this->karmaScore);
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    // ── Role checks ────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPolice(): bool
    {
        return $this->role === 'police';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this user has at least the given role level.
     * Hierarchy: user < police < admin
     */
    public function hasRole(string $minimumRole): bool
    {
        $hierarchy = ['user' => 1, 'police' => 2, 'admin' => 3];

        $userLevel = $hierarchy[$this->role] ?? 0;
        $requiredLevel = $hierarchy[$minimumRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }
}