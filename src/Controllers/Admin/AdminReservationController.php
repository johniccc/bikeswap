<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Repository\ReservationRepository;
use App\Repository\TheftReportRepository;
use App\Response\Response;
use App\Services\AuthService;

class AdminReservationController
{
    private ReservationRepository $reservationRepository;
    private TheftReportRepository $theftReportRepository;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        ReservationRepository $reservationRepository,
        TheftReportRepository $theftReportRepository,
        AuthService $authService,
        Session $session
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Reservation management list.
     * GET /admin/reservations
     */
    public function reservations(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $statusFilter = $request->query('status');

        $reservations = $this->reservationRepository->findAll($statusFilter, withRelations: true);

        return view('admin/reservations', [
            'title' => 'Rezervace — Admin',
            'currentUser' => $currentUser,
            'reservations' => $reservations,
            'statusFilter' => $statusFilter,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Theft reports management list.
     * GET /admin/thefts
     */
    public function thefts(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $statusFilter = $request->query('status');

        $filters = [];
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }

        $reports = $this->theftReportRepository->findAll($filters);

        return view('admin/thefts', [
            'title' => 'Krádeže — Admin',
            'currentUser' => $currentUser,
            'reports' => $reports,
            'statusFilter' => $statusFilter,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }
}
