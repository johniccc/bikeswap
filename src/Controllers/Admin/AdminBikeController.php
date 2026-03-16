<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\FileUploadService;
use App\Services\QRService;

class AdminBikeController
{
    private BikeRepository $bikeRepository;
    private UserRepository $userRepository;
    private AuthService $authService;
    private FileUploadService $fileUploadService;
    private QRService $qrService;
    private ActivityLogService $activityLog;
    private Session $session;

    public function __construct(
        BikeRepository $bikeRepository,
        UserRepository $userRepository,
        AuthService $authService,
        FileUploadService $fileUploadService,
        QRService $qrService,
        ActivityLogService $activityLog,
        Session $session
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->userRepository = $userRepository;
        $this->authService = $authService;
        $this->fileUploadService = $fileUploadService;
        $this->qrService = $qrService;
        $this->activityLog = $activityLog;
        $this->session = $session;
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
     * Bike detail with edit form.
     * GET /admin/bikes/{id}
     */
    public function bikeDetail(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();

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

        $this->activityLog->log('admin_bike_create', 'bike', $bikeId);
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

        $this->activityLog->log('admin_bike_update', 'bike', $bikeId);
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

        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        // Delete photo files from storage
        foreach ($bike->getPhotos() as $photo) {
            $this->fileUploadService->delete($photo->getFilePath());
        }

        $this->bikeRepository->delete($bikeId);
        $this->activityLog->log('admin_bike_delete', 'bike', $bikeId);
        $this->session->flash('success', 'Kolo bylo smazáno.');

        return redirect('/admin/bikes');
    }

    /**
     * Handle photo uploads for admin bike creation/editing.
     */
    private function handleAdminPhotoUploads(array $files, int $bikeId, int $uploadedBy, int $primaryIndex = 0): void
    {
        $normalized = $this->fileUploadService->normalizeFileArray($files);
        if (empty($normalized)) return;

        $isFirstBikePhoto = empty($this->bikeRepository->findPhotosByBikeId($bikeId));
        $primaryIndex = min($primaryIndex, count($normalized) - 1);

        foreach ($normalized as $i => $singleFile) {
            $result = $this->fileUploadService->uploadBikePhoto($singleFile);
            if ($result['success']) {
                $isPrimary = ($i === $primaryIndex && $isFirstBikePhoto);
                $this->bikeRepository->addPhoto($bikeId, $result['path'], $uploadedBy, $isPrimary);
            }
        }
    }
}
