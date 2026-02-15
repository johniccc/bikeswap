<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repository\BikeRepository;
use App\Repository\FoundReportRepository;
use App\Repository\UserRepository;

/**
 * Karma service.
 * 
 * Calculates a "trust score" for registered users based on their activity.
 * The score is cached in users.karma_score and recalculated on relevant actions.
 * 
 * Karma sources:
 *   - Number of found reports submitted (+5 each)
 *   - Number of successfully resolved returns (+20 each)
 *   - Number of registered bikes (+2 each)
 *   - Account age (+1 per month, max 12)
 * 
 * Levels:
 *   0-9     Nováček
 *   10-29   Aktivní
 *   30-59   Důvěryhodný
 *   60+     Ověřený
 */
class KarmaService
{
    private const POINTS_PER_FOUND_REPORT = 5;
    private const POINTS_PER_RESOLVED = 20;
    private const POINTS_PER_BIKE = 2;
    private const POINTS_PER_MONTH = 1;
    private const MAX_MONTH_POINTS = 12;

    private const LEVELS = [
        0  => 'Nováček',
        10 => 'Aktivní',
        30 => 'Důvěryhodný',
        60 => 'Ověřený',
    ];

    private UserRepository $userRepository;
    private FoundReportRepository $foundReportRepository;
    private BikeRepository $bikeRepository;
    private Database $db;

    public function __construct(
        UserRepository $userRepository,
        FoundReportRepository $foundReportRepository,
        BikeRepository $bikeRepository,
        Database $db
    ) {
        $this->userRepository = $userRepository;
        $this->foundReportRepository = $foundReportRepository;
        $this->bikeRepository = $bikeRepository;
        $this->db = $db;
    }

    /**
     * Recalculate and cache the karma score for a user.
     */
    public function recalculate(int $userId): int
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return 0;
        }

        $score = 0;

        // Found reports submitted
        $foundCount = $this->foundReportRepository->countByReporter($userId);
        $score += $foundCount * self::POINTS_PER_FOUND_REPORT;

        // Successfully resolved returns
        $resolvedCount = $this->foundReportRepository->countResolvedByReporter($userId);
        $score += $resolvedCount * self::POINTS_PER_RESOLVED;

        // Registered bikes
        $bikeCount = $this->bikeRepository->countByOwner($userId);
        $score += $bikeCount * self::POINTS_PER_BIKE;

        // Account age (months since registration)
        $createdAt = new \DateTime($user->getCreatedAt());
        $now = new \DateTime();
        $months = (int) $createdAt->diff($now)->format('%m') + ((int) $createdAt->diff($now)->format('%y') * 12);
        $score += min($months * self::POINTS_PER_MONTH, self::MAX_MONTH_POINTS);

        // Cache in database
        $this->db->update('users', ['karma_score' => $score], 'id = ?', [$userId]);

        return $score;
    }

    /**
     * Get the karma level label for a score.
     */
    public static function getLevel(int $score): string
    {
        $level = 'Nováček';

        foreach (self::LEVELS as $threshold => $label) {
            if ($score >= $threshold) {
                $level = $label;
            }
        }

        return $level;
    }

    /**
     * Add or subtract karma points from a user's score.
     * This is used for actions not covered by recalculate() (e.g., reservations, reviews).
     *
     * @param int $userId User ID
     * @param int $points Points to add (positive) or subtract (negative)
     * @param string|null $reason Optional reason for logging/debugging
     * @return int New karma score
     */
    public function addPoints(int $userId, int $points, ?string $reason = null): int
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return 0;
        }

        $newScore = max(0, $user->getKarmaScore() + $points);

        $this->db->update('users', ['karma_score' => $newScore], 'id = ?', [$userId]);

        return $newScore;
    }

    /**
     * Get a display string for use in conversations.
     * e.g. "Důvěryhodný uživatel, 3 úspěšné nálezy"
     */
    public function getDisplayLabel(int $userId): string
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return 'Neregistrovaný uživatel';
        }

        $level = self::getLevel($user->getKarmaScore());
        $resolvedCount = $this->foundReportRepository->countResolvedByReporter($userId);

        if ($resolvedCount > 0) {
            return sprintf('%s uživatel, %d úspěšn%s nález%s',
                $level,
                $resolvedCount,
                $resolvedCount === 1 ? 'ý' : ($resolvedCount < 5 ? 'é' : 'ých'),
                $resolvedCount === 1 ? '' : ($resolvedCount < 5 ? 'y' : 'ů')
            );
        }

        return "{$level} uživatel";
    }
}