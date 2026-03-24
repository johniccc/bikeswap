<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Repository\BikeRepository;
use App\Repository\BikeWarningRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\BikeWarningService;

/**
 * Admin/police management of bike warnings: creating, resolving warnings,
 * seizing bikes, and returning seized bikes to owners.
 */
class AdminBikeWarningController
{
    private BikeWarningRepository $bikeWarningRepository;
    private BikeWarningService $bikeWarningService;
    private BikeRepository $bikeRepository;
    private UserRepository $userRepository;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        BikeWarningRepository $bikeWarningRepository,
        BikeWarningService $bikeWarningService,
        BikeRepository $bikeRepository,
        UserRepository $userRepository,
        AuthService $authService,
        Session $session
    ) {
        $this->bikeWarningRepository = $bikeWarningRepository;
        $this->bikeWarningService = $bikeWarningService;
        $this->bikeRepository = $bikeRepository;
        $this->userRepository = $userRepository;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Bike warnings list.
     * GET /admin/warnings
     */
    public function bikeWarnings(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $tab = $request->query('tab', 'active');

        // Active warnings (always needed for tab count)
        $warnings = $this->bikeWarningRepository->findAll('active', 'warning');

        $bikesMap = [];
        $ownersMap = [];
        foreach ($warnings as $w) {
            if (!isset($bikesMap[$w->getBikeId()])) {
                $bikesMap[$w->getBikeId()] = $this->bikeRepository->findById($w->getBikeId());
            }
            $bike = $bikesMap[$w->getBikeId()] ?? null;
            if ($bike && !isset($ownersMap[$bike->getOwnerId()])) {
                $ownersMap[$bike->getOwnerId()] = $this->userRepository->findById($bike->getOwnerId());
            }
        }

        // Seized bikes — with search filters when on seized tab
        $seizedFilters = [
            'search'       => trim($request->query('search', '')),
            'owner'        => trim($request->query('owner', '')),
            'color'        => trim($request->query('color', '')),
            'year_from'    => $request->query('year_from', ''),
            'year_to'      => $request->query('year_to', ''),
            'frame_number' => trim($request->query('frame_number', '')),
            'qr_hash'      => trim($request->query('qr_hash', '')),
        ];

        $hasSeizedFilters = !empty(array_filter($seizedFilters, fn($v) => $v !== ''));

        $seizedBikes = $this->bikeRepository->findSeized(
            withPhotos: true,
            search: $seizedFilters['search'] ?: null,
            color: $seizedFilters['color'] ?: null,
            yearFrom: $seizedFilters['year_from'] !== '' ? (int) $seizedFilters['year_from'] : null,
            yearTo: $seizedFilters['year_to'] !== '' ? (int) $seizedFilters['year_to'] : null,
            frameNumber: $seizedFilters['frame_number'] ?: null,
            qrHash: $seizedFilters['qr_hash'] ?: null,
            ownerSearch: $seizedFilters['owner'] ?: null,
        );

        $seizedCount = $this->bikeRepository->countAll('seized');
        $yearRange = $this->bikeRepository->getSeizedBikeYearRange();

        foreach ($seizedBikes as $sb) {
            if (!isset($ownersMap[$sb->getOwnerId()])) {
                $ownersMap[$sb->getOwnerId()] = $this->userRepository->findById($sb->getOwnerId());
            }
        }

        return view('admin/bike-warnings', [
            'title' => 'Upozornění na kola — Admin',
            'currentUser' => $currentUser,
            'tab' => $tab,
            'warnings' => $warnings,
            'seizedBikes' => $seizedBikes,
            'seizedCount' => $seizedCount,
            'seizedFilters' => $seizedFilters,
            'hasSeizedFilters' => $hasSeizedFilters,
            'yearMin' => $yearRange['min'],
            'yearMax' => $yearRange['max'],
            'bikesMap' => $bikesMap,
            'ownersMap' => $ownersMap,
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

        $allBikes = $this->bikeRepository->findAll(null, withPhotos: true);

        $preselectedId = $request->query('bike_id');

        return view('admin/bike-warning-create', [
            'title' => 'Nové upozornění — Admin',
            'currentUser' => $currentUser,
            'allBikes' => $allBikes,
            'preselectedId' => $preselectedId ? (int) $preselectedId : null,
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

        $bikeIds = $request->input('bike_ids', []);
        if (!is_array($bikeIds)) {
            $bikeIds = [(int) $bikeIds];
        }
        $bikeIds = array_filter(array_map('intval', $bikeIds), fn($id) => $id > 0);

        $reason = trim($request->input('reason', ''));
        $deadline = $request->input('deadline', '');
        $location = trim($request->input('location', '')) ?: null;

        if (empty($bikeIds) || $reason === '' || $deadline === '') {
            $this->session->flash('error', 'Vyberte alespoň jedno kolo a vyplňte všechna povinná pole.');
            $this->session->setOldInput($_POST);
            return redirect('/admin/warnings/new');
        }

        $created = 0;
        $errors = [];
        foreach ($bikeIds as $bikeId) {
            try {
                $this->bikeWarningService->createWarning(
                    $bikeId,
                    $currentUser->getId(),
                    $reason,
                    $deadline,
                    $location
                );
                $created++;
            } catch (\RuntimeException $e) {
                $errors[] = "Kolo #{$bikeId}: " . $e->getMessage();
            }
        }

        if ($created > 0) {
            $this->session->flash('success', "Vytvořeno {$created} upozornění.");
        }
        if (!empty($errors)) {
            $this->session->flash('error', implode(' ', $errors));
        }

        return redirect('/admin/warnings');
    }

    /**
     * Resolve a bike warning.
     * POST /admin/warnings/{id}/resolve
     */
    public function resolveBikeWarning(Request $request): Response
    {
        $warningId = (int) $request->param('id');
        $this->bikeWarningService->resolveWarning($warningId);
        $this->session->flash('success', 'Upozornění bylo vyřešeno.');

        return redirect('/admin/warnings');
    }

    /**
     * Seize a bike (tow it away).
     * POST /admin/warnings/{bikeId}/seize
     */
    public function seizeBike(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $bikeId = (int) $request->param('bikeId');

        try {
            $this->bikeWarningService->seizeBike($bikeId, $currentUser->getId());
            $this->session->flash('success', 'Kolo bylo označeno jako odvezené.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect('/admin/warnings');
    }

    /**
     * Return a seized bike to its owner.
     * POST /admin/warnings/{bikeId}/return
     */
    public function returnBike(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $bikeId = (int) $request->param('bikeId');

        try {
            $this->bikeWarningService->returnBike($bikeId, $currentUser->getId());
            $this->session->flash('success', 'Kolo bylo vráceno majiteli.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike) {
            return redirect('/admin/bikes/' . $bikeId);
        }

        return redirect('/admin/bikes');
    }
}
