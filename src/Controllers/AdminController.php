<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\FoundReportRepository;
use App\Repository\FoundReportMessageRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReservationMessageRepository;
use App\Repository\TheftReportRepository;
use App\Repository\BikeWarningRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\BikeWarningService;
use App\Services\FileUploadService;
use App\Services\QRService;

class AdminController
{
    private UserRepository $userRepository;
    private BikeRepository $bikeRepository;
    private ReservationRepository $reservationRepository;
    private TheftReportRepository $theftReportRepository;
    private FoundReportRepository $foundReportRepository;
    private ReservationMessageRepository $reservationMessageRepository;
    private FoundReportMessageRepository $foundReportMessageRepository;
    private AuthService $authService;
    private FileUploadService $fileUploadService;
    private QRService $qrService;
    private BikeWarningRepository $bikeWarningRepository;
    private BikeWarningService $bikeWarningService;
    private Session $session;

    public function __construct(
        UserRepository $userRepository,
        BikeRepository $bikeRepository,
        ReservationRepository $reservationRepository,
        TheftReportRepository $theftReportRepository,
        FoundReportRepository $foundReportRepository,
        ReservationMessageRepository $reservationMessageRepository,
        FoundReportMessageRepository $foundReportMessageRepository,
        AuthService $authService,
        FileUploadService $fileUploadService,
        QRService $qrService,
        BikeWarningRepository $bikeWarningRepository,
        BikeWarningService $bikeWarningService,
        Session $session
    ) {
        $this->userRepository = $userRepository;
        $this->bikeRepository = $bikeRepository;
        $this->reservationRepository = $reservationRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->foundReportRepository = $foundReportRepository;
        $this->reservationMessageRepository = $reservationMessageRepository;
        $this->foundReportMessageRepository = $foundReportMessageRepository;
        $this->authService = $authService;
        $this->fileUploadService = $fileUploadService;
        $this->qrService = $qrService;
        $this->bikeWarningRepository = $bikeWarningRepository;
        $this->bikeWarningService = $bikeWarningService;
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
            'warnings_active' => $this->bikeWarningRepository->countByStatus('active'),
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

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/users/{$userId}");
        }

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

        $this->userRepository->adminUpdateUser($userId, [
            'first_name' => trim($request->input('first_name', '')),
            'surname'    => trim($request->input('surname', '')),
            'email'      => $newEmail,
            'phone'      => preg_replace('/\s+/', '', trim($request->input('phone', ''))) ?: null,
            'address'    => trim($request->input('address', '')) ?: null,
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

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/users/{$userId}");
        }

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
        $this->session->flash('success', 'Uživatel byl smazán.');

        return redirect('/admin/users');
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

    // ── Bike CRUD ───────────────────────────────────────────────

    /**
     * Bike detail with edit form.
     * GET /admin/bikes/{id}
     */
    public function bikeDetail(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $bikeId = (int) $request->param('id');
        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        $owner = $this->userRepository->findById($bike->getOwnerId());

        return view('admin/bike-detail', [
            'title' => $bike->getFullName() . ' — Admin',
            'currentUser' => $currentUser,
            'bike' => $bike,
            'owner' => $owner,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Admin bike create form.
     * GET /admin/bikes/new
     */
    public function createBikeForm(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $preselectedOwnerId = (int) $request->query('owner', '0');
        $users = $this->userRepository->findAll();

        return view('admin/bike-create', [
            'title' => 'Přidat kolo — Admin',
            'currentUser' => $currentUser,
            'users' => $users,
            'preselectedOwnerId' => $preselectedOwnerId,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Create a bike (admin).
     * POST /admin/bikes/new
     */
    public function createBike(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/admin/bikes/new');
        }

        $validator = new Validator($request->all());
        $validator
            ->required('owner_id', 'Vyberte vlastníka kola.')
            ->required('brand', 'Značka je povinná.')
            ->required('color', 'Barva je povinná.')
            ->integer('owner_id', 'Neplatný vlastník.');

        if ($request->input('year_of_manufacture')) {
            $validator->integer('year_of_manufacture', 'Rok výroby musí být číslo.');
        }

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect('/admin/bikes/new');
        }

        $ownerId = (int) $request->input('owner_id');
        $owner = $this->userRepository->findById($ownerId);

        if ($owner === null) {
            $this->session->flash('error', 'Vybraný uživatel neexistuje.');
            return redirect('/admin/bikes/new');
        }

        $qrHash = $this->qrService->generateUniqueHash();

        $bikeId = $this->bikeRepository->create([
            'owner_id'            => $ownerId,
            'qr_hash'             => $qrHash,
            'brand'               => $request->input('brand'),
            'model'               => $request->input('model'),
            'color'               => $request->input('color'),
            'frame_number'        => $request->input('frame_number'),
            'year_of_manufacture' => $request->input('year_of_manufacture') ?: null,
            'description'         => $request->input('description'),
            'is_shared'           => $request->input('is_shared') ? 1 : 0,
        ]);

        // Handle photo upload(s)
        $photos = $request->file('photos');
        if ($photos !== null) {
            $primaryIndex = max(0, (int) $request->input('primary_index', '0'));
            $this->handleAdminPhotoUploads($photos, $bikeId, $currentUser->getId(), $primaryIndex);
        }

        $this->session->flash('success', 'Kolo bylo úspěšně vytvořeno.');
        return redirect("/admin/bikes/{$bikeId}");
    }

    /**
     * Update a bike (admin).
     * POST /admin/bikes/{id}/edit
     */
    public function updateBike(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $bikeId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/bikes/{$bikeId}");
        }

        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        $validator = new Validator($request->all());
        $validator
            ->required('brand', 'Značka je povinná.')
            ->required('color', 'Barva je povinná.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect("/admin/bikes/{$bikeId}");
        }

        $this->bikeRepository->update($bikeId, [
            'brand'               => $request->input('brand'),
            'model'               => $request->input('model'),
            'color'               => $request->input('color'),
            'frame_number'        => $request->input('frame_number'),
            'year_of_manufacture' => $request->input('year_of_manufacture') ?: null,
            'description'         => $request->input('description'),
            'is_shared'           => $request->input('is_shared') ? 1 : 0,
        ]);

        $newStatus = $request->input('status', '');
        if (in_array($newStatus, ['active', 'stolen', 'found', 'recovered'], true) && $newStatus !== $bike->getStatus()) {
            $this->bikeRepository->updateStatus($bikeId, $newStatus);
        }

        // Handle new photo upload(s)
        $photos = $request->file('photos');
        if ($photos !== null) {
            $primaryIndex = max(0, (int) $request->input('primary_index', '0'));
            $this->handleAdminPhotoUploads($photos, $bikeId, $currentUser->getId(), $primaryIndex);
        }

        $this->session->flash('success', 'Kolo bylo úspěšně upraveno.');
        return redirect("/admin/bikes/{$bikeId}");
    }

    /**
     * Delete a bike (admin).
     * POST /admin/bikes/{id}/delete
     */
    public function deleteBike(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $bikeId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/bikes/{$bikeId}");
        }

        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        // Delete photo files from storage
        foreach ($bike->getPhotos() as $photo) {
            $this->fileUploadService->delete($photo->getFilePath());
        }

        $this->bikeRepository->delete($bikeId);
        $this->session->flash('success', 'Kolo bylo smazáno.');

        return redirect('/admin/bikes');
    }

    // ── Conversations ───────────────────────────────────────────

    /**
     * List all conversations (reservations + found reports).
     * GET /admin/conversations
     */
    public function conversations(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $reservations = $this->reservationRepository->findAll(null, withRelations: true);
        $foundReports = $this->foundReportRepository->findAll();

        return view('admin/conversations', [
            'title' => 'Konverzace — Admin',
            'currentUser' => $currentUser,
            'reservations' => $reservations,
            'foundReports' => $foundReports,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Admin view of a reservation conversation.
     * GET /admin/conversation/reservation/{id}
     */
    public function reservationConversation(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $reservationId = (int) $request->param('id');
        $reservation = $this->reservationRepository->findById($reservationId, withRelations: true);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        $messages = $this->reservationMessageRepository->findByReservationId($reservationId);
        $this->reservationMessageRepository->markAsReadForViewer($reservationId, 'owner');

        $owner = $this->userRepository->findById($reservation->getOwnerId());
        $borrower = $this->userRepository->findById($reservation->getBorrowerId());
        $bike = $this->bikeRepository->findById($reservation->getBikeId(), withPhotos: true);

        return view('admin/conversation-reservation', [
            'title' => 'Konverzace rezervace #' . $reservationId . ' — Admin',
            'currentUser' => $currentUser,
            'reservation' => $reservation,
            'messages' => $messages,
            'owner' => $owner,
            'borrower' => $borrower,
            'bike' => $bike,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Admin sends a message to a reservation conversation.
     * POST /admin/conversation/reservation/{id}/message
     */
    public function sendReservationMessage(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $reservationId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/conversation/reservation/{$reservationId}");
        }

        $message = trim($request->input('message', ''));
        if ($message === '') {
            $this->session->flash('error', 'Zpráva nesmí být prázdná.');
            return redirect("/admin/conversation/reservation/{$reservationId}");
        }

        $reservation = $this->reservationRepository->findById($reservationId);
        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        $this->reservationMessageRepository->create($reservationId, 'admin', $currentUser->getId(), $message);

        return redirect("/admin/conversation/reservation/{$reservationId}");
    }

    /**
     * Admin view of a found report conversation.
     * GET /admin/conversation/found/{id}
     */
    public function foundConversation(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $reportId = (int) $request->param('id');
        $report = $this->foundReportRepository->findById($reportId);

        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $messages = $this->foundReportMessageRepository->findByReportId($reportId);
        $bike = $report->getBikeId() ? $this->bikeRepository->findById($report->getBikeId(), withPhotos: true) : null;

        $owner = null;
        if ($bike !== null) {
            $owner = $this->userRepository->findById($bike->getOwnerId());
        }

        return view('admin/conversation-found', [
            'title' => 'Konverzace nálezu #' . $reportId . ' — Admin',
            'currentUser' => $currentUser,
            'report' => $report,
            'messages' => $messages,
            'bike' => $bike,
            'owner' => $owner,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Admin sends a message to a found report conversation.
     * POST /admin/conversation/found/{id}/message
     */
    public function sendFoundMessage(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $reportId = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/admin/conversation/found/{$reportId}");
        }

        $message = trim($request->input('message', ''));
        if ($message === '') {
            $this->session->flash('error', 'Zpráva nesmí být prázdná.');
            return redirect("/admin/conversation/found/{$reportId}");
        }

        $report = $this->foundReportRepository->findById($reportId);
        if ($report === null) {
            throw new \RuntimeException('Hlášení nenalezeno.', 404);
        }

        $this->foundReportMessageRepository->create($reportId, 'admin', $currentUser->getId(), $message);

        return redirect("/admin/conversation/found/{$reportId}");
    }

    // ── Bike Warnings ────────────────────────────────────────────

    /**
     * Bike warnings list.
     * GET /admin/warnings
     */
    public function bikeWarnings(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $statusFilter = $request->query('status');
        $warnings = $this->bikeWarningRepository->findAll($statusFilter);

        $bikesMap = [];
        $creatorsMap = [];
        $ownersMap = [];
        foreach ($warnings as $w) {
            if (!isset($bikesMap[$w->getBikeId()])) {
                $bikesMap[$w->getBikeId()] = $this->bikeRepository->findById($w->getBikeId());
            }
            if (!isset($creatorsMap[$w->getCreatedBy()])) {
                $creatorsMap[$w->getCreatedBy()] = $this->userRepository->findById($w->getCreatedBy());
            }
            $bike = $bikesMap[$w->getBikeId()] ?? null;
            if ($bike && !isset($ownersMap[$bike->getOwnerId()])) {
                $ownersMap[$bike->getOwnerId()] = $this->userRepository->findById($bike->getOwnerId());
            }
        }

        $counts = [
            'all' => $this->bikeWarningRepository->countByStatus(),
            'active' => $this->bikeWarningRepository->countByStatus('active'),
            'resolved' => $this->bikeWarningRepository->countByStatus('resolved'),
            'expired' => $this->bikeWarningRepository->countByStatus('expired'),
        ];

        return view('admin/bike-warnings', [
            'title' => 'Upozornění na kola — Admin',
            'currentUser' => $currentUser,
            'warnings' => $warnings,
            'bikesMap' => $bikesMap,
            'creatorsMap' => $creatorsMap,
            'ownersMap' => $ownersMap,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Create bike warning form.
     * GET /admin/warnings/new
     */
    public function createBikeWarningForm(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $bikeId = $request->query('bike_id');
        $bike = $bikeId ? $this->bikeRepository->findById((int) $bikeId) : null;

        return view('admin/bike-warning-create', [
            'title' => 'Nové upozornění — Admin',
            'currentUser' => $currentUser,
            'bike' => $bike,
            'bikeId' => $bikeId,
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Store a new bike warning.
     * POST /admin/warnings/new
     */
    public function storeBikeWarning(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný CSRF token.');
            return redirect('/admin/warnings/new');
        }

        $bikeId = (int) $request->input('bike_id', '0');
        $reason = trim($request->input('reason', ''));
        $deadline = $request->input('deadline', '');
        $location = trim($request->input('location', '')) ?: null;

        if ($bikeId <= 0 || $reason === '' || $deadline === '') {
            $this->session->flash('error', 'Vyplňte všechna povinná pole.');
            $this->session->setOldInput($_POST);
            return redirect('/admin/warnings/new?bike_id=' . $bikeId);
        }

        try {
            $this->bikeWarningService->createWarning(
                $bikeId,
                $currentUser->getId(),
                $reason,
                $deadline,
                $location
            );
            $this->session->flash('success', 'Upozornění bylo vytvořeno a vlastník byl informován.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
            $this->session->setOldInput($_POST);
            return redirect('/admin/warnings/new?bike_id=' . $bikeId);
        }

        return redirect('/admin/warnings');
    }

    /**
     * Resolve a bike warning.
     * POST /admin/warnings/{id}/resolve
     */
    public function resolveBikeWarning(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný CSRF token.');
            return redirect('/admin/warnings');
        }

        $warningId = (int) $request->param('id');
        $this->bikeWarningService->resolveWarning($warningId);
        $this->session->flash('success', 'Upozornění bylo vyřešeno.');

        return redirect('/admin/warnings');
    }

    /**
     * Handle photo uploads for admin bike creation/editing.
     */
    private function handleAdminPhotoUploads(array $files, int $bikeId, int $uploadedBy, int $primaryIndex = 0): void
    {
        if (isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            $count = count($files['tmp_name']);
            $isFirstBikePhoto = empty($this->bikeRepository->findPhotosByBikeId($bikeId));
            $primaryIndex = min($primaryIndex, $count - 1);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $singleFile = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name'     => $files['name'][$i],
                    'size'     => $files['size'][$i],
                    'type'     => $files['type'][$i],
                    'error'    => $files['error'][$i],
                ];

                $result = $this->fileUploadService->uploadBikePhoto($singleFile);

                if ($result['success']) {
                    $isPrimary = ($i === $primaryIndex && $isFirstBikePhoto);
                    $this->bikeRepository->addPhoto($bikeId, $result['path'], $uploadedBy, $isPrimary);
                }
            }
        } elseif (isset($files['tmp_name']) && $files['error'] === UPLOAD_ERR_OK) {
            $result = $this->fileUploadService->uploadBikePhoto($files);

            if ($result['success']) {
                $isFirstBikePhoto = empty($this->bikeRepository->findPhotosByBikeId($bikeId));
                $this->bikeRepository->addPhoto($bikeId, $result['path'], $uploadedBy, $isFirstBikePhoto);
            }
        }
    }
}
