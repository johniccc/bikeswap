<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\BikeRepository;
use App\Repository\ReservationRepository;
use App\Repository\TheftReportRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;

class AdminController
{
    private UserRepository $userRepository;
    private BikeRepository $bikeRepository;
    private ReservationRepository $reservationRepository;
    private TheftReportRepository $theftReportRepository;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        UserRepository $userRepository,
        BikeRepository $bikeRepository,
        ReservationRepository $reservationRepository,
        TheftReportRepository $theftReportRepository,
        AuthService $authService,
        Session $session
    ) {
        $this->userRepository = $userRepository;
        $this->bikeRepository = $bikeRepository;
        $this->reservationRepository = $reservationRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->authService = $authService;
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
            'bikes_stolen' => $this->bikeRepository->countAll('stolen'),
            'thefts_open' => $this->theftReportRepository->countOpen(),
        ];

        if ($isAdmin) {
            $stats['users'] = $this->userRepository->countAll();
            $stats['reservations'] = $this->reservationRepository->countAll();
            $stats['reservations_pending'] = $this->reservationRepository->countAll('pending');
        }

        $disputes = $isAdmin ? $this->reservationRepository->findDisputed(withRelations: true) : [];

        return view('admin/dashboard', [
            'title' => 'Administrace',
            'currentUser' => $currentUser,
            'isAdmin' => $isAdmin,
            'stats' => $stats,
            'disputes' => $disputes,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * User management list.
     * GET /admin/users
     */
    public function users(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $roleFilter = $request->query('role');
        $users = $this->userRepository->findAll($roleFilter);

        return view('admin/users', [
            'title' => 'Uživatelé — Admin',
            'currentUser' => $currentUser,
            'users' => $users,
            'roleFilter' => $roleFilter,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * User detail.
     * GET /admin/users/{id}
     */
    public function userDetail(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $userId = (int) $request->param('id');
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \RuntimeException('Uživatel nenalezen.', 404);
        }

        $bikes = $this->bikeRepository->findByOwner($userId, withPhotos: true);

        return view('admin/user-detail', [
            'title' => $user->getName() . ' — Admin',
            'currentUser' => $currentUser,
            'user' => $user,
            'bikes' => $bikes,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Ban a user.
     * POST /admin/users/{id}/ban
     */
    public function banUser(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $userId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/users/{$userId}");
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('Uživatel nenalezen.', 404);
        }

        if ($user->getId() === $currentUser->getId()) {
            $this->session->flash('error', 'Nemůžete zablokovat sami sebe.');
            return redirect("/admin/users/{$userId}");
        }

        if ($user->isAdmin()) {
            $this->session->flash('error', 'Nemůžete zablokovat jiného administrátora.');
            return redirect("/admin/users/{$userId}");
        }

        $this->userRepository->ban($userId);
        $this->session->flash('success', 'Uživatel byl zablokován.');

        return redirect("/admin/users/{$userId}");
    }

    /**
     * Unban a user.
     * POST /admin/users/{id}/unban
     */
    public function unbanUser(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $userId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/users/{$userId}");
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('Uživatel nenalezen.', 404);
        }

        $this->userRepository->unban($userId);
        $this->session->flash('success', 'Uživatel byl odblokován.');

        return redirect("/admin/users/{$userId}");
    }

    /**
     * Change user role.
     * POST /admin/users/{id}/role
     */
    public function changeRole(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $userId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/users/{$userId}");
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('Uživatel nenalezen.', 404);
        }

        if ($user->getId() === $currentUser->getId()) {
            $this->session->flash('error', 'Nemůžete změnit roli sami sobě.');
            return redirect("/admin/users/{$userId}");
        }

        $newRole = $request->input('role', '');
        if (!in_array($newRole, ['user', 'police', 'admin'], true)) {
            $this->session->flash('error', 'Neplatná role.');
            return redirect("/admin/users/{$userId}");
        }

        $this->userRepository->updateRole($userId, $newRole);
        $this->session->flash('success', 'Role uživatele byla změněna.');

        return redirect("/admin/users/{$userId}");
    }

    /**
     * Bike management list.
     * GET /admin/bikes
     */
    public function bikes(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $statusFilter = $request->query('status');

        $bikes = $this->bikeRepository->findAll($statusFilter, withPhotos: true);

        return view('admin/bikes', [
            'title' => 'Kola — Admin',
            'currentUser' => $currentUser,
            'bikes' => $bikes,
            'statusFilter' => $statusFilter,
            'session' => $this->session,
        ])->withLayout('layouts/app');
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
