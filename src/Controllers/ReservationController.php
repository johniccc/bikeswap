<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeExcludedDateRepository;
use App\Repository\BikeRepository;
use App\Repository\DisputePhotoRepository;
use App\Repository\ReservationRepository;
use App\Repository\ReservationMessageRepository;
use App\Repository\ReservationReviewRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\FileUploadService;
use App\Services\ReservationService;

class ReservationController
{
    private ReservationRepository $reservationRepo;
    private ReservationMessageRepository $messageRepo;
    private ReservationReviewRepository $reviewRepo;
    private DisputePhotoRepository $disputePhotoRepo;
    private BikeExcludedDateRepository $excludedDateRepo;
    private BikeRepository $bikeRepo;
    private ReservationService $reservationService;
    private FileUploadService $fileUploadService;
    private AuthService $authService;
    private Session $session;
    private array $config;

    public function __construct(
        ReservationRepository $reservationRepo,
        ReservationMessageRepository $messageRepo,
        ReservationReviewRepository $reviewRepo,
        DisputePhotoRepository $disputePhotoRepo,
        BikeExcludedDateRepository $excludedDateRepo,
        BikeRepository $bikeRepo,
        ReservationService $reservationService,
        FileUploadService $fileUploadService,
        AuthService $authService,
        Session $session,
        array $config = []
    ) {
        $this->reservationRepo = $reservationRepo;
        $this->messageRepo = $messageRepo;
        $this->reviewRepo = $reviewRepo;
        $this->disputePhotoRepo = $disputePhotoRepo;
        $this->excludedDateRepo = $excludedDateRepo;
        $this->bikeRepo = $bikeRepo;
        $this->reservationService = $reservationService;
        $this->fileUploadService = $fileUploadService;
        $this->authService = $authService;
        $this->session = $session;
        $this->config = $config;
    }

    // ── Shared bikes list ──────────────────────────────────

    /**
     * List all bikes available for borrowing.
     * GET /shared
     */
    public function sharedBikes(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $layout = $currentUser ? 'layouts/app' : 'layouts/public';

        $search   = trim($request->query('search', '')) ?: null;
        $color    = trim($request->query('color', '')) ?: null;
        $yearFrom = ($y = (int) $request->query('year_from', 0)) > 0 ? $y : null;
        $yearTo   = ($y = (int) $request->query('year_to', 0)) > 0 ? $y : null;
        $dateFrom = trim($request->query('date_from', '')) ?: null;
        $dateTo   = trim($request->query('date_to', '')) ?: null;

        $excludeIds = [];
        if ($dateFrom !== null && $dateTo !== null && $dateFrom <= $dateTo) {
            $excludeIds = $this->reservationRepo->findBikeIdsWithConflict($dateFrom, $dateTo);
        }

        $bikes     = $this->bikeRepo->findShared(
            withPhotos: true,
            search: $search,
            color: $color,
            yearFrom: $yearFrom,
            yearTo: $yearTo,
            excludeIds: $excludeIds
        );
        $yearRange = $this->bikeRepo->getSharedBikeYearRange();

        $bikeIds            = array_map(fn($b) => $b->getId(), $bikes);
        $activeReservations = $this->reservationRepo->findCurrentByBikeIds($bikeIds);

        $filters = [
            'search'    => $search ?? '',
            'color'     => $color ?? '',
            'year_from' => $yearFrom ?? '',
            'year_to'   => $yearTo ?? '',
            'date_from' => $dateFrom ?? '',
            'date_to'   => $dateTo ?? '',
        ];
        $hasFilters = !empty(array_filter($filters, fn($v) => $v !== ''));

        return view('reservation/shared-bikes', [
            'title'              => 'Sdílená kola – BikeSwap',
            'bikes'              => $bikes,
            'activeReservations' => $activeReservations,
            'yearMin'            => $yearRange['min'],
            'yearMax'            => $yearRange['max'],
            'filters'            => $filters,
            'hasFilters'         => $hasFilters,
            'currentUser'        => $currentUser,
            'session'            => $this->session,
        ])->withLayout($layout);
    }

    // ── Create reservation ─────────────────────────────────

    /**
     * Show reservation form with date picker.
     * GET /reservation/new/{bikeId}
     */
    public function createForm(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if ($currentUser && $currentUser->isPolice()) {
            $this->session->flash('error', 'Policejní účet nemůže vytvářet rezervace.');
            return redirect('/shared');
        }

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

        // Get availability settings for calendar
        $availabilityDays = $bike->getAvailabilityDaysArray();
        $excludedDates = $bike->isAutoAccept()
            ? $this->excludedDateRepo->findByBikeId($bikeId)
            : [];

        return view('reservation/create', [
            'title' => 'Rezervovat kolo – BikeSwap',
            'bike' => $bike,
            'unavailableDates' => $unavailableDates,
            'availabilityDays' => $availabilityDays,
            'excludedDates' => $excludedDates,
            'currentUser' => $currentUser,
            'turnstileSiteKey' => $this->config['turnstile']['site_key'] ?? '',
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Process reservation request.
     * POST /reservation/new/{bikeId}
     */
    public function store(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        if ($currentUser && $currentUser->isPolice()) {
            $this->session->flash('error', 'Policejní účet nemůže vytvářet rezervace.');
            return redirect('/shared');
        }

        $bikeId = (int) $request->param('bikeId');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/new/{$bikeId}");
        }

        // Turnstile CAPTCHA verification
        $secretKey = $this->config['turnstile']['secret_key'] ?? '';
        if ($secretKey !== '') {
            $token = $request->input('cf-turnstile-response', '');
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => $secretKey,
                'response' => $token,
            ]));
            $response = curl_exec($ch);
            $curlError = curl_errno($ch);
            curl_close($ch);

            if ($curlError !== 0 || $response === false) {
                $this->session->setOldInput($request->all());
                $this->session->flash('error', 'Ověření proti botům selhalo (chyba spojení). Zkuste to prosím znovu.');
                return redirect("/reservation/new/{$bikeId}");
            }

            $result = json_decode($response, true);

            if (empty($result['success'])) {
                $this->session->setOldInput($request->all());
                $this->session->flash('error', 'Ověření proti botům selhalo. Zkuste to prosím znovu.');
                return redirect("/reservation/new/{$bikeId}");
            }
        }

        $validator = new Validator($request->all());
        $validator
            ->required('date_from', 'Datum začátku je povinné.')
            ->required('date_to', 'Datum konce je povinné.');

        if ($validator->fails()) {
            $this->session->setOldInput($request->all());
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

            // Check if reservation was auto-approved
            $reservation = $this->reservationRepo->findById($result['reservation_id']);
            if ($reservation !== null && $reservation->isApproved()) {
                $this->session->flash('success', 'Rezervace byla automaticky schválena!');
            } else {
                $this->session->flash('success', 'Žádost o výpůjčku byla odeslána!');
            }
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

        // Load dispute evidence photos
        $disputePhotos = $this->disputePhotoRepo->findByReservation($id);

        return view('reservation/detail', [
            'title' => 'Rezervace #' . $id . ' – BikeSwap',
            'reservation' => $reservation,
            'messages' => $messages,
            'reviews' => $reviews,
            'hasReviewed' => $hasReviewed,
            'disputePhotos' => $disputePhotos,
            'currentUser' => $currentUser,
            'myRole' => $myRole,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
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
     * Show form for reporting non-return.
     * GET /reservation/{id}/not-returned
     */
    public function notReturnedForm(Request $request): Response
    {
        $id = (int) $request->param('id');
        $reservation = $this->reservationRepo->findById($id, withRelations: true);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        $currentUser = $this->authService->currentUser();

        if (!$reservation->isOwnedBy($currentUser->getId())) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$reservation->canReportNotReturned()) {
            $this->session->flash('error', 'Nelze nahlásit nevrácení.');
            return redirect("/reservation/{$id}");
        }

        return view('reservation/not-returned', [
            'title' => 'Nahlásit nevrácení — BikeSwap',
            'reservation' => $reservation,
            'currentUser' => $currentUser,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Process non-return report.
     * POST /reservation/{id}/not-returned
     */
    public function reportNotReturned(Request $request): Response
    {
        $id = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/{$id}/not-returned");
        }

        $currentUser = $this->authService->currentUser();
        $reason = trim($request->input('reason', ''));

        if ($reason === '') {
            $this->session->flash('error', 'Popis situace je povinný.');
            return redirect("/reservation/{$id}/not-returned");
        }

        try {
            $this->reservationService->reportNotReturned($id, $currentUser->getId(), $reason);
            $this->session->flash('success', 'Nevrácení kola bylo nahlášeno. Případ bude řešen správcem.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/reservation/{$id}");
    }

    /**
     * Show form for filing a dispute.
     * GET /reservation/{id}/dispute
     */
    public function disputeForm(Request $request): Response
    {
        $id = (int) $request->param('id');
        $reservation = $this->reservationRepo->findById($id, withRelations: true);

        if ($reservation === null) {
            throw new \RuntimeException('Rezervace nenalezena.', 404);
        }

        $currentUser = $this->authService->currentUser();

        if (!$reservation->isBorrowedBy($currentUser->getId())) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$reservation->canBeDisputed()) {
            $this->session->flash('error', 'Tuto akci nelze provést.');
            return redirect("/reservation/{$id}");
        }

        return view('reservation/dispute', [
            'title' => 'Podat námitku — BikeSwap',
            'reservation' => $reservation,
            'currentUser' => $currentUser,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Process dispute submission.
     * POST /reservation/{id}/dispute
     */
    public function dispute(Request $request): Response
    {
        $id = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/{$id}/dispute");
        }

        $currentUser = $this->authService->currentUser();
        $reason = trim($request->input('reason', ''));

        if ($reason === '') {
            $this->session->flash('error', 'Vysvětlení je povinné.');
            return redirect("/reservation/{$id}/dispute");
        }

        // Handle evidence photo uploads
        $photoPaths = [];
        $files = $request->file('photos');
        if ($files !== null && isset($files['tmp_name'])) {
            if (is_array($files['tmp_name'])) {
                $count = count($files['tmp_name']);
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
                    $result = $this->fileUploadService->uploadReportPhoto($singleFile);
                    if ($result['success']) {
                        $photoPaths[] = $result['path'];
                    }
                }
            } elseif ($files['error'] === UPLOAD_ERR_OK) {
                $result = $this->fileUploadService->uploadReportPhoto($files);
                if ($result['success']) {
                    $photoPaths[] = $result['path'];
                }
            }
        }

        try {
            $this->reservationService->dispute($id, $currentUser->getId(), $reason, $photoPaths);
            $this->session->flash('success', 'Vaše námitka byla zaznamenána. Případ bude řešen správcem.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/reservation/{$id}");
    }

    /**
     * Admin resolves a dispute.
     * POST /reservation/{id}/admin-resolve
     */
    public function adminResolve(Request $request): Response
    {
        $id = (int) $request->param('id');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect("/reservation/{$id}");
        }

        $currentUser = $this->authService->currentUser();

        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $resolution = $request->input('resolution', '');

        try {
            $this->reservationService->adminResolve($id, $currentUser->getId(), $resolution);
            $this->session->flash('success', 'Spor byl vyřešen.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect("/reservation/{$id}");
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

        $filterBikeId = (int) $request->query('bike', 0) ?: null;
        $filterBike   = null;

        if ($filterBikeId !== null) {
            $filterBike = $this->bikeRepo->findById($filterBikeId);
            // Only allow filtering by own bikes
            if ($filterBike === null || !$filterBike->isOwnedBy($currentUser->getId())) {
                $filterBikeId = null;
                $filterBike   = null;
            }
        }

        if ($filterBikeId !== null) {
            $asOwner = $this->reservationRepo->findByOwnerAndBike($currentUser->getId(), $filterBikeId, withRelations: true);
        } else {
            $asOwner = $this->reservationRepo->findByOwner($currentUser->getId(), withRelations: true);
        }

        $asBorrower = $this->reservationRepo->findByBorrower($currentUser->getId(), withRelations: true);

        // Load all owner's bikes for the filter dropdown
        $ownerBikes = $this->bikeRepo->findByOwner($currentUser->getId());

        // Find overdue reservations for reminder banner
        $overdue = $this->reservationRepo->findOverdueByOwner($currentUser->getId(), withRelations: true);

        // Get IDs of reservations the user has already reviewed
        $reviewedIds = $this->reviewRepo->getReviewedReservationIds($currentUser->getId());

        return view('reservation/my-reservations', [
            'title'       => 'Moje rezervace – BikeSwap',
            'asOwner'     => $asOwner,
            'asBorrower'  => $asBorrower,
            'overdue'     => $overdue,
            'reviewedIds' => $reviewedIds,
            'filterBike'  => $filterBike,
            'ownerBikes'  => $ownerBikes,
            'currentUser' => $currentUser,
            'session'     => $this->session,
        ])->withLayout('layouts/app');
    }

    /**
     * Lightweight poll for reservation list changes.
     * GET /reservations/poll
     */
    public function reservationsPoll(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $userId = $currentUser->getId();

        $asOwner = $this->reservationRepo->findByOwner($userId);
        $asBorrower = $this->reservationRepo->findByBorrower($userId);

        $ownerStatuses = array_map(fn($r) => $r->getId() . ':' . $r->getStatus(), $asOwner);
        $borrowerStatuses = array_map(fn($r) => $r->getId() . ':' . $r->getStatus(), $asBorrower);

        return json([
            'owner_statuses'    => implode(',', $ownerStatuses),
            'borrower_statuses' => implode(',', $borrowerStatuses),
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