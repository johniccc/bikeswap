<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\NotificationRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\NotificationService;

class NotificationController
{
    private NotificationRepository $repository;
    private NotificationService $service;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        NotificationRepository $repository,
        NotificationService $service,
        AuthService $authService,
        Session $session
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Show notifications list.
     * GET /notifications
     */
    public function index(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $notifications = $this->repository->findByUserId($currentUser->getId());

        if ($request->wantsJson()) {
            return json([
                'notifications' => array_map(fn($n) => [
                    'id' => $n->getId(),
                    'type' => $n->getType(),
                    'title' => $n->getTitle(),
                    'message' => $n->getMessage(),
                    'link' => $n->getLink(),
                    'is_read' => $n->isRead(),
                    'time' => $n->getFormattedTime(),
                ], $notifications),
            ]);
        }

        return view('notifications/index', [
            'title' => 'Oznámení – BikeSwap',
            'notifications' => $notifications,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Mark a single notification as read.
     * POST /notifications/{id}/read
     *
     * If the notification has a link, redirects there.
     * Otherwise redirects back to notifications list.
     */
    public function markAsRead(Request $request): Response
    {
        $id = (int) $request->param('id');
        $currentUser = $this->authService->currentUser();

        // Verify ownership
        $notification = $this->repository->findById($id);

        if ($notification === null || $notification->getUserId() !== $currentUser->getId()) {
            if ($request->wantsJson()) {
                return json(['error' => 'Oznámení nenalezeno.'], 404);
            }

            $this->session->flash('error', 'Oznámení nenalezeno.');

            return redirect('/notifications');
        }

        $this->service->markAsRead($id);

        if ($request->wantsJson()) {
            return json(['success' => true]);
        }

        // Redirect to notification link if available, otherwise to list
        $redirectTo = $notification->getLink() ?? '/notifications';

        return redirect($redirectTo);
    }

    /**
     * Mark all notifications as read.
     * POST /notifications/read-all
     */
    public function markAllAsRead(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $this->service->markAllAsRead($currentUser->getId());

        if ($request->wantsJson()) {
            return json(['success' => true]);
        }

        $this->session->flash('success', 'Všechna oznámení označena jako přečtená.');

        return redirect('/notifications');
    }

    /**
     * Get unread count (AJAX endpoint for badge).
     * GET /notifications/count
     */
    public function unreadCount(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $count = $this->service->getUnreadCount($currentUser->getId());

        return json(['unread_count' => $count]);
    }
}