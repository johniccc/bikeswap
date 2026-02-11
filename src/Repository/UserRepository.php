<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\User;

/**
 * Repository for the users table.
 * 
 * Handles all database queries related to users.
 * Returns User entities, never raw arrays.
 */
class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find a user by ID. Returns null if not found.
     */
    public function findById(int $id): ?User
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Find a user by email. Returns null if not found.
     */
    public function findByEmail(string $email): ?User
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Find a user by verification token.
     */
    public function findByVerificationToken(string $token): ?User
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM users WHERE verification_token = ?",
            [$token]
        );

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Check if an email is already registered.
     */
    public function emailExists(string $email): bool
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE email = ?",
            [$email]
        );

        return (int) $count > 0;
    }

    /**
     * Create a new user. Returns the new user's ID.
     */
    public function create(array $data): int
    {
        return $this->db->insert('users', [
            'email'              => $data['email'],
            'password_hash'      => $data['password_hash'],
            'name'               => $data['name'],
            'phone'              => $data['phone'] ?? null,
            'address'            => $data['address'] ?? null,
            'role'               => $data['role'] ?? 'user',
            'is_verified'        => $data['is_verified'] ?? 0,
            'verification_token' => $data['verification_token'] ?? null,
        ]);
    }

    /**
     * Update a user's last login timestamp.
     */
    public function updateLastLogin(int $userId): void
    {
        $this->db->update(
            'users',
            ['last_login_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userId]
        );
    }

    /**
     * Mark a user as verified.
     */
    public function verify(int $userId): void
    {
        $this->db->update(
            'users',
            [
                'is_verified'        => 1,
                'verification_token' => null,
            ],
            'id = ?',
            [$userId]
        );
    }

    /**
     * Update user profile fields.
     */
    public function updateProfile(int $userId, array $data): void
    {
        $allowed = ['name', 'phone', 'address'];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if (empty($filtered)) {
            return;
        }

        $this->db->update('users', $filtered, 'id = ?', [$userId]);
    }

    /**
     * Update a user's password hash.
     */
    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->db->update(
            'users',
            ['password_hash' => $passwordHash],
            'id = ?',
            [$userId]
        );
    }

    /**
     * Update a user's role (admin only).
     */
    public function updateRole(int $userId, string $role): void
    {
        $this->db->update(
            'users',
            ['role' => $role],
            'id = ?',
            [$userId]
        );
    }

    /**
     * Get all users (for admin panel), optionally filtered by role.
     * 
     * @return User[]
     */
    public function findAll(?string $role = null): array
    {
        if ($role !== null) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM users WHERE role = ? ORDER BY created_at DESC",
                [$role]
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT * FROM users ORDER BY created_at DESC"
            );
        }

        return array_map(fn(array $row) => User::fromRow($row), $rows);
    }

    /**
     * Delete a user by ID.
     */
    public function delete(int $userId): void
    {
        $this->db->delete('users', 'id = ?', [$userId]);
    }
}