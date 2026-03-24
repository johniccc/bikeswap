<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Session;
use App\Repository\ActivityLogRepository;

/**
 * Records user actions for audit purposes.
 *
 * Automatically captures the current user, IP address, and user agent
 * from the active session/request context.
 */
class ActivityLogService
{
    private ActivityLogRepository $repo;
    private Session $session;
    private Request $request;

    public function __construct(ActivityLogRepository $repo, Session $session, Request $request)
    {
        $this->repo = $repo;
        $this->session = $session;
        $this->request = $request;
    }

    /**
     * Record an action in the activity log.
     *
     * @param string     $action     Action identifier (e.g. 'login', 'register', 'login_failed')
     * @param string     $entityType Entity type being acted upon (e.g. 'user', 'bike')
     * @param int|null   $entityId   ID of the affected entity
     * @param array|null $oldValue   Previous state for change tracking
     * @param array|null $newValue   New state for change tracking
     * @return void
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null
    ): void {
        $this->repo->log(
            userId: $this->session->userId(),
            action: $action,
            entityType: $entityType,
            entityId: $entityId,
            oldValue: $oldValue,
            newValue: $newValue,
            ipAddress: $this->request->ip(),
            userAgent: $this->request->userAgent()
        );
    }
}
