<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class TwoFactorRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function enableTotp(int $userId, string $secret): void
    {
        $this->db->update('users', [
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ], 'id = ?', [$userId]);
    }

    public function disableTotp(int $userId): void
    {
        $this->db->update('users', [
            'totp_secret'  => null,
            'totp_enabled' => 0,
        ], 'id = ?', [$userId]);
    }

    public function getSecret(int $userId): ?string
    {
        return $this->db->fetchColumn(
            "SELECT totp_secret FROM users WHERE id = ?",
            [$userId]
        ) ?: null;
    }

    public function storeRecoveryCodes(int $userId, array $hashes): void
    {
        foreach ($hashes as $hash) {
            $this->db->insert('user_recovery_codes', [
                'user_id'   => $userId,
                'code_hash' => $hash,
            ]);
        }
    }

    public function deleteRecoveryCodes(int $userId): void
    {
        $this->db->delete('user_recovery_codes', 'user_id = ?', [$userId]);
    }

    public function getUnusedCodes(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT id, code_hash FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        );
    }

    public function markCodeUsed(int $codeId): void
    {
        $this->db->update('user_recovery_codes', [
            'used_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$codeId]);
    }

    public function countUnusedCodes(int $userId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM user_recovery_codes WHERE user_id = ? AND used_at IS NULL",
            [$userId]
        );
    }
}
