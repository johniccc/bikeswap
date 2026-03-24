<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Repository\ActivityLogRepository;
use App\Repository\BikeRepository;
use App\Repository\BikeWarningRepository;
use App\Repository\ReservationRepository;
use App\Repository\TheftReportRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;

/**
 * Renders the admin/police dashboard with platform statistics and actionable items.
 */
class AdminDashboardController
{
    private BikeRepository $bikeRepository;
    private ReservationRepository $reservationRepository;
    private TheftReportRepository $theftReportRepository;
    private BikeWarningRepository $bikeWarningRepository;
    private ActivityLogRepository $activityLogRepository;
    private AuthService $authService;
    private UserRepository $userRepository;
    private Session $session;

    public function __construct(
        BikeRepository $bikeRepository,
        ReservationRepository $reservationRepository,
        TheftReportRepository $theftReportRepository,
        BikeWarningRepository $bikeWarningRepository,
        ActivityLogRepository $activityLogRepository,
        AuthService $authService,
        UserRepository $userRepository,
        Session $session
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->reservationRepository = $reservationRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->bikeWarningRepository = $bikeWarningRepository;
        $this->activityLogRepository = $activityLogRepository;
        $this->authService = $authService;
        $this->userRepository = $userRepository;
        $this->session = $session;
    }

    /**
     * Admin dashboard with statistics and actionable items.
     * GET /admin
     */
    public function dashboard(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();

        $isAdmin = $currentUser->isAdmin();

        $stats = [
            'bikes' => $this->bikeRepository->countAll(),
            'thefts_open' => $this->theftReportRepository->countOpen(),
            'warnings_active' => $this->bikeWarningRepository->countByStatus('active'),
        ];

        if ($isAdmin) {
            $stats['reservations'] = $this->reservationRepository->countAll();
            $stats['reservations_pending'] = $this->reservationRepository->countAll('pending');
            $stats['users'] = $this->userRepository->countAll();
        }

        $disputes = $this->reservationRepository->findDisputed(withRelations: true);

        $recentActivity = $isAdmin ? $this->activityLogRepository->findAllWithUsers(20) : [];

        return view('admin/dashboard', [
            'title' => 'Administrace',
            'currentUser' => $currentUser,
            'isAdmin' => $isAdmin,
            'stats' => $stats,
            'disputes' => $disputes,
            'recentActivity' => $recentActivity,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }
}
