<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class CookieConsentRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function store(string $sessionId, ?int $userId, string $consent, ?string $ipAddress): int
    {
        $existing = $this->findBySessionId($sessionId);

        if ($existing) {
            $this->db->update('cookie_consents', [
                'consent_given' => $consent,
                'user_id'       => $userId,
                'ip_address'    => $ipAddress,
            ], 'id = ?', [(int) $existing['id']]);

            return (int) $existing['id'];
        }

        return $this->db->insert('cookie_consents', [
            'session_id'    => $sessionId,
            'user_id'       => $userId,
            'consent_given' => $consent,
            'ip_address'    => $ipAddress,
        ]);
    }

    public function findBySessionId(string $sessionId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM cookie_consents WHERE session_id = ? ORDER BY updated_at DESC LIMIT 1",
            [$sessionId]
        );
    }

    public function hasConsent(string $sessionId): ?string
    {
        $row = $this->findBySessionId($sessionId);

        return $row ? $row['consent_given'] : null;
    }
}
