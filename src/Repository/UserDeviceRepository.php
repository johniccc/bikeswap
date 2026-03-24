<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Entity\UserDevice;

/**
 * Repository for the user_devices table.
 *
 * Tracks browser fingerprints for security monitoring and
 * suspicious login detection.
 */
class UserDeviceRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return UserDevice[]
     */
    public function findByUserId(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM user_devices WHERE user_id = ? ORDER BY last_seen_at DESC",
            [$userId]
        );

        return array_map([UserDevice::class, 'fromRow'], $rows);
    }

    /**
     * Insert or update a device record for a user.
     *
     * Uses ON DUPLICATE KEY UPDATE to refresh the last_seen_at timestamp
     * and device attributes on subsequent logins from the same fingerprint.
     */
    public function upsert(int $userId, string $fingerprintHash, array $data): void
    {
        $this->db->query(
            "INSERT INTO user_devices (user_id, fingerprint_hash, ip_address, user_agent, screen_resolution, timezone, language, platform, color_depth, touch_support, do_not_track)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                screen_resolution = VALUES(screen_resolution),
                timezone = VALUES(timezone),
                language = VALUES(language),
                platform = VALUES(platform),
                color_depth = VALUES(color_depth),
                touch_support = VALUES(touch_support),
                do_not_track = VALUES(do_not_track),
                last_seen_at = NOW()",
            [
                $userId,
                $fingerprintHash,
                $data['ip_address'] ?? null,
                $data['user_agent'] ?? null,
                $data['screen_resolution'] ?? null,
                $data['timezone'] ?? null,
                $data['language'] ?? null,
                $data['platform'] ?? null,
                $data['color_depth'] ?? null,
                $data['touch_support'] ?? null,
                $data['do_not_track'] ?? null,
            ]
        );
    }
}
