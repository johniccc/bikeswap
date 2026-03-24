<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Database operations for cookie consent records.
 *
 * Tracks per-session consent choices, updating existing records
 * if the user changes their preference.
 */
class CookieConsentRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Store or update a consent record for the given session.
     * Returns the consent record ID.
     */
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

    /**
     * Find the most recent consent record for a session.
     */
    public function findBySessionId(string $sessionId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM cookie_consents WHERE session_id = ? ORDER BY updated_at DESC LIMIT 1",
            [$sessionId]
        );
    }

    /**
     * Get the consent value for a session, or null if no consent exists.
     */
    public function hasConsent(string $sessionId): ?string
    {
        $row = $this->findBySessionId($sessionId);

        return $row ? $row['consent_given'] : null;
    }
}
