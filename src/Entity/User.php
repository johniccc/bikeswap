<?php

declare(strict_types=1);

namespace App\Entity;

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
    private string $name;
    private ?string $phone;
    private ?string $address;
    private bool $isVerified;
    private ?string $verificationToken;
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
        $user->name              = $row['name'];
        $user->phone             = $row['phone'] ?? null;
        $user->address           = $row['address'] ?? null;
        $user->isVerified        = (bool) ($row['is_verified'] ?? false);
        $user->verificationToken = $row['verification_token'] ?? null;
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

    public function getName(): string
    {
        return $this->name;
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