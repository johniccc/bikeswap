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
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

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
        $currentUser = $this->authService->currentUser();
        if (!$currentUser->hasRole('police')) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $warningId = (int) $request->param('id');
        $this->bikeWarningService->resolveWarning($warningId);
        $this->session->flash('success', 'Upozornění bylo vyřešeno.');

        return redirect('/admin/warnings');
    }
}
