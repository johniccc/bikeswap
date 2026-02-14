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
    private NotificationRepository $notificationRepository;
    private NotificationService $notificationService;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        NotificationRepository $notificationRepository,
        NotificationService $notificationService,
        AuthService $authService,
        Session $session
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->notificationService = $notificationService;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Show all notifications for the current user.
     * GET /notifications
     */
    public function index(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $notifications = $this->notificationRepository->findByUserId($currentUser->getId());

        if ($request->wantsJson()) {
            return json([
                'notifications' => array_map(fn($n) => [
                    'id' => $n->getId(),
                    'type' => $n->getType(),
                    'title' => $n->getTitle(),
                    'message' => $n->getMessage(),
                    'link' => $n->getLink(),
                    'is_read' => $n->isRead(),
                    'created_at' => $n->getCreatedAt(),
                ], $notifications),
                'unread_count' => $this->notificationService->getUnreadCount($currentUser->getId()),
            ]);
        }

        return view('notifications/index', [
            'title' => 'Oznámení – BikeSwap',
            'notifications' => $notifications,
            'session' => $this->session,
        ]);
    }

    /**
     * Mark a single notification as read.
     * POST /notifications/{id}/read
     */
    public function markAsRead(Request $request): Response
    {
        $notificationId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            if ($request->wantsJson()) {
                return json(['error' => 'Neplatný token.'], 403);
            }

            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect('/notifications');
        }

        $this->notificationService->markAsRead($notificationId);

        if ($request->wantsJson()) {
            return json(['success' => true]);
        }

        // If notification has a link, redirect there
        $notification = $this->notificationRepository->findByUserId(
            $this->authService->currentUser()->getId()
        );

        // Find the specific notification to get its link
        foreach ($notification as $n) {
            if ($n->getId() === $notificationId && $n->getLink() !== null) {
                return redirect($n->getLink());
            }
        }

        return redirect('/notifications');
    }

    /**
     * Mark all notifications as read.
     * POST /notifications/read-all
     */
    public function markAllAsRead(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            if ($request->wantsJson()) {
                return json(['error' => 'Neplatný token.'], 403);
            }

            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect('/notifications');
        }

        $this->notificationService->markAllAsRead($currentUser->getId());

        if ($request->wantsJson()) {
            return json(['success' => true]);
        }

        $this->session->flash('success', 'Všechna oznámení označena jako přečtená.');

        return redirect('/notifications');
    }

    /**
     * Get unread count (for AJAX badge updates).
     * GET /notifications/count
     */
    public function unreadCount(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();

        return json([
            'unread_count' => $this->notificationService->getUnreadCount($currentUser->getId()),
        ]);
    }
}