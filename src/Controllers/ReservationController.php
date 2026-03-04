<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReservationMessageRepository;
use App\Repository\ReservationReviewRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\ReservationService;

class ReservationController
{
    private ReservationRepository $reservationRepo;
    private ReservationMessageRepository $messageRepo;
    private ReservationReviewRepository $reviewRepo;
    private BikeRepository $bikeRepo;
    private ReservationService $reservationService;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        ReservationRepository $reservationRepo,
        ReservationMessageRepository $messageRepo,
        ReservationReviewRepository $reviewRepo,
        BikeRepository $bikeRepo,
        ReservationService $reservationService,
        AuthService $authService,
        Session $session
    ) {
        $this->reservationRepo = $reservationRepo;
        $this->messageRepo = $messageRepo;
        $this->reviewRepo = $reviewRepo;
        $this->bikeRepo = $bikeRepo;
        $this->reservationService = $reservationService;
        $this->authService = $authService;
        $this->session = $session;
    }

    // ── Shared bikes list ──────────────────────────────────

    /**
     * List all bikes available for borrowing.
     * GET /shared
     */
    public function sharedBikes(Request $request): Response
    {
        $bikes = $this->bikeRepo->findShared(withPhotos: true);
        $currentUser = $this->authService->currentUser();
        $unavailableIds = $this->reservationRepo->findUnavailableBikeIds();

        return view('reservation/shared-bikes', [
            'title' => 'Sdílená kola – BikeSwap',
            'bikes' => $bikes,
            'currentUser' => $currentUser,
            'unavailableIds' => $unavailableIds,
            'session' => $this->session,
        ]);
    }

    // ── Create reservation ─────────────────────────────────

    /**
     * Show reservation form with date picker.
     * GET /reservation/new/{bikeId}
     */
    public function createForm(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');
        $bike = $this->bikeRepo->findById($bikeId, withPhotos: true);

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isShared()) {
            throw new \RuntimeException('Toto kolo není nabízeno k výpůjčce.', 403);
        }

        $currentUser = $this->authService->currentUser();

        if ($bike->isOwnedBy($currentUser->getId())) {
            $this->session->flash('error', 'Nemůžete si půjčit vlastní kolo.');
            return redirect('/shared');
        }

        // Get unavailable dates for calendar
        $unavailableDates = $this->reservationService->getUnavailableDates($bikeId);

        return view('reservation/create', [
            'title' => 'Rezervovat kolo – BikeSwap',
            'bike' => $bike,
            'unavailableDates' => $unavailableDates,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    /**
     * Process reservation request.
     * POST /reservation/new/{bikeId}
     */
    public function store(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/new/{$bikeId}");
        }

        $validator = new Validator($request->all());
        $validator
            ->required('date_from', 'Datum začátku je povinné.')
            ->required('date_to', 'Datum konce je povinné.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect("/reservation/new/{$bikeId}");
        }

        $currentUser = $this->authService->currentUser();

        try {
            $result = $this->reservationService->createReservation(
                $bikeId,
                $currentUser->getId(),
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('message')
            );

            $this->session->flash('success', 'Žádost o výpůjčku byla odeslána!');
            return redirect('/reservation/' . $result['reservation_id']);
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
            return redirect("/reservation/new/{$bikeId}");
        }
    }

    // ── Reservation detail + conversation ──────────────────

    /**
     * Show reservation detail with conversation.
     * GET /reservation/{id}
     */
    public function detail(Request $request): Response
    {
        $id = (int) $request->param('id');
        $reservation = $this->reservationRepo->findById($id, withRelations: true);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        $currentUser = $this->authService->currentUser();

        if (!$reservation->involvesUser($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        // Load messages
        $messages = $this->messageRepo->findByReservationId($id);

        // Mark other party's messages as read
        $myRole = $reservation->getUserRole($currentUser->getId());
        $this->messageRepo->markAsReadForViewer($id, $myRole);

        // Load revealed reviews
        $reviews = $this->reviewRepo->findRevealedByReservation($id);

        // Check if current user already reviewed
        $hasReviewed = $this->reservationService->hasUserReviewed($id, $currentUser->getId());

        return view('reservation/detail', [
            'title' => 'Rezervace #' . $id . ' – BikeSwap',
            'reservation' => $reservation,
            'messages' => $messages,
            'reviews' => $reviews,
            'hasReviewed' => $hasReviewed,
            'currentUser' => $currentUser,
            'myRole' => $myRole,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    // ── Status actions ─────────────────────────────────────

    /**
     * Owner approves reservation.
     * POST /reservation/{id}/approve
     */
    public function approve(Request $request): Response
    {
        return $this->handleStatusAction($request, 'approve', 'Rezervace byla schválena.');
    }

    /**
     * Owner rejects reservation.
     * POST /reservation/{id}/reject
     */
    public function reject(Request $request): Response
    {
        return $this->handleStatusAction($request, 'reject', 'Rezervace byla zamítnuta.');
    }

    /**
     * Either party cancels.
     * POST /reservation/{id}/cancel
     */
    public function cancel(Request $request): Response
    {
        return $this->handleStatusAction($request, 'cancel', 'Rezervace byla zrušena.');
    }

    /**
     * Owner activates (bike picked up).
     * POST /reservation/{id}/activate
     */
    public function activate(Request $request): Response
    {
        return $this->handleStatusAction($request, 'activate', 'Výpůjčka byla zahájena.');
    }

    /**
     * Owner completes (bike returned).
     * POST /reservation/{id}/complete
     */
    public function complete(Request $request): Response
    {
        return $this->handleStatusAction($request, 'complete', 'Výpůjčka byla dokončena.');
    }

    /**
     * Owner reports non-return.
     * POST /reservation/{id}/not-returned
     */
    public function reportNotReturned(Request $request): Response
    {
        return $this->handleStatusAction($request, 'reportNotReturned', 'Nevrácení kola bylo nahlášeno.');
    }

    // ── Messaging ──────────────────────────────────────────

    /**
     * Send a message in the reservation conversation.
     * POST /reservation/{id}/message
     */
    public function sendMessage(Request $request): Response
    {
        $id = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/{$id}");
        }

        $message = trim($request->input('message', ''));

        if ($message === '') {
            $this->session->flash('error', 'Zpráva nemůže být prázdná.');
            return redirect("/reservation/{$id}");
        }

        $currentUser = $this->authService->currentUser();

        try {
            $this->reservationService->sendMessage($id, $currentUser->getId(), $message);
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/reservation/{$id}");
    }

    // ── Reviews ────────────────────────────────────────────

    /**
     * Submit a review.
     * POST /reservation/{id}/review
     */
    public function submitReview(Request $request): Response
    {
        $id = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/{$id}");
        }

        $rating = (int) $request->input('rating', '0');
        $comment = trim($request->input('comment', ''));

        if ($rating < 1 || $rating > 5) {
            $this->session->flash('error', 'Vyberte hodnocení 1–5 hvězdiček.');
            return redirect("/reservation/{$id}");
        }

        $currentUser = $this->authService->currentUser();

        try {
            $this->reservationService->submitReview(
                $id,
                $currentUser->getId(),
                $rating,
                $comment ?: null
            );

            $this->session->flash('success', 'Hodnocení bylo odesláno.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/reservation/{$id}");
    }

    // ── My reservations list ───────────────────────────────

    /**
     * List all reservations for the current user.
     * GET /reservations
     */
    public function myReservations(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();

        $asOwner = $this->reservationRepo->findByOwner($currentUser->getId(), withRelations: true);
        $asBorrower = $this->reservationRepo->findByBorrower($currentUser->getId(), withRelations: true);

        // Find overdue reservations for reminder banner
        $overdue = $this->reservationRepo->findOverdueByOwner($currentUser->getId(), withRelations: true);

        return view('reservation/my-reservations', [
            'title' => 'Moje rezervace – BikeSwap',
            'asOwner' => $asOwner,
            'asBorrower' => $asBorrower,
            'overdue' => $overdue,
            'currentUser' => $currentUser,
            'session' => $this->session,
        ]);
    }

    // ── JSON endpoint for calendar ─────────────────────────

    /**
     * Return unavailable dates for a bike (used by JS calendar).
     * GET /reservation/{bikeId}/unavailable-dates
     */
    public function unavailableDates(Request $request): Response
    {
        $bikeId = (int) $request->param('bikeId');

        $ranges = $this->reservationService->getUnavailableDates($bikeId);

        return json($ranges);
    }

    // ── Internal helpers ───────────────────────────────────

    /**
     * Generic handler for status-change POST actions.
     */
    private function handleStatusAction(Request $request, string $method, string $successMessage): Response
    {
        $id = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/{$id}");
        }

        $currentUser = $this->authService->currentUser();

        try {
            if ($method === 'cancel') {
                $this->reservationService->cancel($id, $currentUser->getId());
            } else {
                $this->reservationService->$method($id, $currentUser->getId());
            }

            $this->session->flash('success', $successMessage);
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/reservation/{$id}");
    }
}