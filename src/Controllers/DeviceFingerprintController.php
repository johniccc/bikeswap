<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\UserDeviceRepository;
use App\Response\Response;

/**
 * Receives browser fingerprint data and stores a hashed device profile.
 *
 * Used for security monitoring — fingerprints are hashed server-side
 * so raw device attributes are never persisted.
 */
class DeviceFingerprintController
{
    private UserDeviceRepository $deviceRepo;
    private Session $session;

    public function __construct(UserDeviceRepository $deviceRepo, Session $session)
    {
        $this->deviceRepo = $deviceRepo;
        $this->session = $session;
    }

    /**
     * Receive browser fingerprint data and persist a hashed device profile.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request): Response
    {
        $userId = $this->session->userId();
        if ($userId === null) {
            return json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $screenResolution = $this->sanitize((string) ($data['screenResolution'] ?? ''), 20);
        $timezone = $this->sanitize((string) ($data['timezone'] ?? ''), 100);
        $language = $this->sanitize((string) ($data['language'] ?? ''), 20);
        $platform = $this->sanitize((string) ($data['platform'] ?? ''), 100);
        $colorDepth = (int) ($data['colorDepth'] ?? 0);
        $touchSupport = (bool) ($data['touchSupport'] ?? false);
        $doNotTrack = (bool) ($data['doNotTrack'] ?? false);

        // Build fingerprint string and hash server-side
        $fingerprintData = implode('|', [
            $screenResolution,
            $timezone,
            $language,
            $platform,
            $colorDepth,
            $touchSupport ? '1' : '0',
            $doNotTrack ? '1' : '0',
        ]);
        $fingerprintHash = hash('sha256', $fingerprintData);

        $this->deviceRepo->upsert($userId, $fingerprintHash, [
            'ip_address'        => $request->ip(),
            'user_agent'        => mb_substr($request->userAgent(), 0, 500),
            'screen_resolution' => $screenResolution,
            'timezone'          => $timezone,
            'language'          => $language,
            'platform'          => $platform,
            'color_depth'       => $colorDepth ?: null,
            'touch_support'     => $touchSupport ? 1 : 0,
            'do_not_track'      => $doNotTrack ? 1 : 0,
        ]);

        return json(['success' => true]);
    }

    private function sanitize(string $value, int $maxLength): string
    {
        $value = trim($value);
        $value = strip_tags($value);

        return mb_substr($value, 0, $maxLength);
    }
}
