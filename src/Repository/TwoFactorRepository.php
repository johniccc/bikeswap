<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Database operations for two-factor authentication.
 *
 * Manages TOTP secrets on the users table and recovery codes
 * in the user_recovery_codes table.
 */
class TwoFactorRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Enable TOTP for a user by storing the secret and setting the flag.
     */
    public function enableTotp(int $userId, string $secret): void
    {
        $this->db->update('users', [
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ], 'id = ?', [$userId]);
    }

    /**
     * Disable TOTP for a user by clearing the secret and flag.
     */
    public function disableTotp(int $userId): void
    {
        $this->db->update('users', [
            'totp_secret'  => null,
            'totp_enabled' => 0,
        ], 'id = ?', [$userId]);
    }

    /**
     * Get the TOTP secret for a user, or null if not set.
     */
    public function getSecret(int $userId): ?string
    {
        return $this->db->fetchColumn(
            "SELECT totp_secret FROM users WHERE id = ?",
            [$userId]
        ) ?: null;
    }

    /**
     * Store hashed recovery codes for a user.
     *
     * @param string[] $hashes Bcrypt hashes of the recovery codes
     */
    public function storeRecoveryCodes(int $userId, array $hashes): void
    {
        foreach ($hashes as $hash) {
            $this->db->insert('user_recovery_codes', [
                'user_id'   => $userId,
                'code_hash' => $hash,
            ]);
        }
    }

    /**
     * Delete all recovery codes for a user (used when regenerating).
     */
    public function deleteRecoveryCodes(int $userId): void
    {
        $this->db->delete('user_recovery_codes', 'user_id = ?', [$userId]);
    }

    /**
     * Get all unused recovery codes for a user.
     *
     * @return array<array{id: int, code_hash: string}>
     */
    public function getUnusedCodes(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT id, code_hash FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        );
    }

    /**
     * Mark a recovery code as used by setting its used_at timestamp.
     */
    public function markCodeUsed(int $codeId): void
    {
        $this->db->update('user_recovery_codes', [
            'used_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$codeId]);
    }

    /**
     * Count remaining unused recovery codes for a user.
     */
    public function countUnusedCodes(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        );
    }
}
