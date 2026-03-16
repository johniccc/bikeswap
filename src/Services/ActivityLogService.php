<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Session;
use App\Repository\ActivityLogRepository;

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
