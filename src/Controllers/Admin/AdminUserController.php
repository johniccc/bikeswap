<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Repository\ActivityLogRepository;
use App\Repository\BikeRepository;
use App\Repository\UserDeviceRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\ActivityLogService;
use App\Services\AuthService;

/**
 * Admin user management: listing, detail view, banning/unbanning,
 * role changes, profile editing, and account deletion.
 */
class AdminUserController
{
    private UserRepository $userRepository;
    private BikeRepository $bikeRepository;
    private AuthService $authService;
    private ActivityLogService $activityLog;
    private ActivityLogRepository $activityLogRepository;
    private UserDeviceRepository $userDeviceRepository;
    private Session $session;

    public function __construct(
        UserRepository $userRepository,
        BikeRepository $bikeRepository,
        AuthService $authService,
        ActivityLogService $activityLog,
        ActivityLogRepository $activityLogRepository,
        UserDeviceRepository $userDeviceRepository,
        Session $session
    ) {
        $this->userRepository = $userRepository;
        $this->bikeRepository = $bikeRepository;
        $this->authService = $authService;
        $this->activityLog = $activityLog;
        $this->activityLogRepository = $activityLogRepository;
        $this->userDeviceRepository = $userDeviceRepository;
        $this->session = $session;
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
        $devices = $this->userDeviceRepository->findByUserId($userId);
        $activityLogs = $this->activityLogRepository->findByUserId($userId);

        return view('admin/user-detail', [
            'title' => $user->getFullName() . ' — Admin',
            'currentUser' => $currentUser,
            'user' => $user,
            'bikes' => $bikes,
            'devices' => $devices,
            'activityLogs' => $activityLogs,
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
        $this->activityLog->log('admin_ban', 'user', $userId);
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

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('Uživatel nenalezen.', 404);
        }

        $this->userRepository->unban($userId);
        $this->activityLog->log('admin_unban', 'user', $userId);
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
        $this->activityLog->log('admin_role_change', 'user', $userId, ['role' => $user->getRole()], ['role' => $newRole]);
        $this->session->flash('success', 'Role uživatele byla změněna.');

        return redirect("/admin/users/{$userId}");
    }

    /**
     * Update user profile (admin).
     * POST /admin/users/{id}/edit
     */
    public function updateUser(Request $request): Response
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

        $newEmail = strtolower(trim($request->input('email', '')));

        // Validate email uniqueness if it changed
        if ($newEmail !== strtolower($user->getEmail())) {
            if ($this->userRepository->emailExists($newEmail)) {
                $this->session->flash('error', 'Tento e-mail je již zaregistrován.');
                return redirect("/admin/users/{$userId}");
            }
        }

        $address = trim($request->input('address', ''));
        if ($address !== '' && $request->input('address_validated', '0') !== '1') {
            $this->session->flash('error', 'Vyberte adresu z nabídky.');
            return redirect("/admin/users/{$userId}");
        }

        $this->userRepository->adminUpdateUser($userId, [
            'first_name' => trim($request->input('first_name', '')),
            'surname'    => trim($request->input('surname', '')),
            'email'      => $newEmail,
            'phone'      => preg_replace('/\s+/', '', trim($request->input('phone', ''))) ?: null,
            'address'    => $address ?: null,
        ]);

        $this->session->flash('success', 'Údaje uživatele byly aktualizovány.');
        return redirect("/admin/users/{$userId}");
    }

    /**
     * Delete a user (admin only).
     * POST /admin/users/{id}/delete
     */
    public function deleteUser(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $userId = (int) $request->param('id');

        if ($userId === $currentUser->getId()) {
            $this->session->flash('error', 'Nemůžete smazat sami sebe.');
            return redirect("/admin/users/{$userId}");
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \RuntimeException('Uživatel nenalezen.', 404);
        }

        if ($user->isAdmin()) {
            $this->session->flash('error', 'Nemůžete smazat jiného administrátora.');
            return redirect("/admin/users/{$userId}");
        }

        $this->userRepository->delete($userId);
        $this->activityLog->log('admin_user_delete', 'user', $userId);
        $this->session->flash('success', 'Uživatel byl smazán.');

        return redirect('/admin/users');
    }
}
