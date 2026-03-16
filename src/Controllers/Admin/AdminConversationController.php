<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Repository\BikeRepository;
use App\Repository\FoundReportMessageRepository;
use App\Repository\FoundReportRepository;
use App\Repository\ReservationMessageRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;

class AdminConversationController
{
    private ReservationRepository $reservationRepository;
    private ReservationMessageRepository $reservationMessageRepository;
    private FoundReportRepository $foundReportRepository;
    private FoundReportMessageRepository $foundReportMessageRepository;
    private BikeRepository $bikeRepository;
    private UserRepository $userRepository;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        ReservationRepository $reservationRepository,
        ReservationMessageRepository $reservationMessageRepository,
        FoundReportRepository $foundReportRepository,
        FoundReportMessageRepository $foundReportMessageRepository,
        BikeRepository $bikeRepository,
        UserRepository $userRepository,
        AuthService $authService,
        Session $session
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->reservationMessageRepository = $reservationMessageRepository;
        $this->foundReportRepository = $foundReportRepository;
        $this->foundReportMessageRepository = $foundReportMessageRepository;
        $this->bikeRepository = $bikeRepository;
        $this->userRepository = $userRepository;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * List all conversations (reservations + found reports).
     * GET /admin/conversations
     */
    public function conversations(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();

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
}
