<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\TheftReportRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\FileUploadService;
use App\Services\QRService;

class BikeController
{
    private BikeRepository $bikeRepository;
    private TheftReportRepository $theftReportRepository;
    private UserRepository $userRepository;
    private AuthService $authService;
    private FileUploadService $fileUploadService;
    private QRService $qrService;
    private Session $session;

    public function __construct(
        BikeRepository $bikeRepository,
        TheftReportRepository $theftReportRepository,
        UserRepository $userRepository,
        AuthService $authService,
        FileUploadService $fileUploadService,
        QRService $qrService,
        Session $session
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->theftReportRepository = $theftReportRepository;
        $this->userRepository = $userRepository;
        $this->authService = $authService;
        $this->fileUploadService = $fileUploadService;
        $this->qrService = $qrService;
        $this->session = $session;
    }

    /**
     * Public bike detail page (accessed via QR code scan).
     * Shows limited info for non-authenticated users.
     * GET /bike/{hash}
     */
    public function publicDetail(Request $request): Response
    {
        $hash = $request->param('hash');
        $bike = $this->bikeRepository->findByQrHash($hash, withPhotos: true);

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        $currentUser = $this->authService->currentUser();
        $isOwner = $currentUser !== null && $bike->isOwnedBy($currentUser->getId());

        if ($request->wantsJson()) {
            return json([
                'bike' => [
                    'brand' => $bike->getBrand(),
                    'model' => $bike->getModel(),
                    'color' => $bike->getColor(),
                    'status' => $bike->getStatus(),
                    'is_stolen' => $bike->isStolen(),
                ],
            ]);
        }

        return view('bike/public-detail', [
            'title' => $bike->getFullName() . ' – BikeSwap',
            'bike' => $bike,
            'currentUser' => $currentUser,
            'isOwner' => $isOwner,
            'theftReport' => $bike->isStolen() ? $this->theftReportRepository->findActiveByBikeId($bike->getId()) : null,
            'qrDataUri' => $this->qrService->generateQrDataUri($hash),
            'session' => $this->session,
            'csrf' => $this->session->csrfToken(),
        ]);
    }

    /**
     * Show the bike registration form.
     * GET /bike/new
     */
    public function createForm(Request $request): Response
    {
        return view('bike/create', [
            'title' => 'Registrovat kolo – BikeSwap',
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    /**
     * Process bike registration.
     * POST /bike/new
     */
    public function store(Request $request): Response
    {
        // CSRF
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect('/bike/new');
        }

        // Validate
        $validator = new Validator($request->all());
        $validator
            ->required('brand', 'Značka je povinná.')
            ->required('color', 'Barva je povinná.')
            ->maxLength('brand', 100)
            ->maxLength('model', 100)
            ->maxLength('color', 50)
            ->maxLength('frame_number', 100);

        if ($request->input('year_of_manufacture')) {
            $validator->integer('year_of_manufacture', 'Rok výroby musí být číslo.');
        }

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect('/bike/new');
        }

        $currentUser = $this->authService->currentUser();

        // Generate unique QR hash
        $qrHash = $this->qrService->generateUniqueHash();

        // Create bike
        $bikeId = $this->bikeRepository->create([
            'owner_id'             => $currentUser->getId(),
            'qr_hash'              => $qrHash,
            'brand'                => $request->input('brand'),
            'model'                => $request->input('model'),
            'color'                => $request->input('color'),
            'frame_number'         => $request->input('frame_number'),
            'year_of_manufacture'  => $request->input('year_of_manufacture') ?: null,
            'description'          => $request->input('description'),
            'is_shared'            => $request->input('is_shared') ? 1 : 0,
        ]);

        // Handle photo upload(s)
        $photos = $request->file('photos');
        if ($photos !== null) {
            $this->handlePhotoUploads($photos, $bikeId, $currentUser->getId());
        }

        if ($request->wantsJson()) {
            return json(['bike_id' => $bikeId, 'qr_hash' => $qrHash], 201);
        }

        $this->session->flash('success', 'Kolo bylo úspěšně zaregistrováno!');

        return redirect('/bike/' . $qrHash);
    }

    /**
     * Show edit form for a bike.
     * GET /bike/{id}/edit
     */
    public function editForm(Request $request): Response
    {
        $bikeId = (int) $request->param('id');
        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění upravovat toto kolo.', 403);
        }

        return view('bike/edit', [
            'title' => 'Upravit kolo – BikeSwap',
            'bike' => $bike,
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    /**
     * Process bike edit.
     * POST /bike/{id}/edit
     */
    public function update(Request $request): Response
    {
        $bikeId = (int) $request->param('id');
        $bike = $this->bikeRepository->findById($bikeId);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/bike/{$bikeId}/edit");
        }

        // Validate
        $validator = new Validator($request->all());
        $validator
            ->required('brand', 'Značka je povinná.')
            ->required('color', 'Barva je povinná.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect("/bike/{$bikeId}/edit");
        }

        // Update
        $this->bikeRepository->update($bikeId, [
            'brand'                => $request->input('brand'),
            'model'                => $request->input('model'),
            'color'                => $request->input('color'),
            'frame_number'         => $request->input('frame_number'),
            'year_of_manufacture'  => $request->input('year_of_manufacture') ?: null,
            'description'          => $request->input('description'),
            'is_shared'            => $request->input('is_shared') ? 1 : 0,
        ]);

        // Handle new photo uploads
        $photos = $request->file('photos');
        if ($photos !== null) {
            $this->handlePhotoUploads($photos, $bikeId, $currentUser->getId());
        }

        $this->session->flash('success', 'Kolo bylo úspěšně upraveno.');

        return redirect('/bike/' . $bike->getQrHash());
    }

    /**
     * Delete a bike.
     * POST /bike/{id}/delete
     */
    public function delete(Request $request): Response
    {
        $bikeId = (int) $request->param('id');
        $bike = $this->bikeRepository->findById($bikeId, withPhotos: true);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        // Delete photo files from storage
        foreach ($bike->getPhotos() as $photo) {
            $this->fileUploadService->delete($photo->getFilePath());
        }

        // Delete bike (CASCADE deletes photo records)
        $this->bikeRepository->delete($bikeId);

        $this->session->flash('success', 'Kolo bylo smazáno.');

        return redirect('/dashboard');
    }

    /**
     * User's bike list (dashboard).
     * GET /dashboard
     */
    public function myBikes(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $bikes = $this->bikeRepository->findByOwner($currentUser->getId(), withPhotos: true);

        if ($request->wantsJson()) {
            return json(['bikes' => array_map(fn($b) => [
                'id' => $b->getId(),
                'brand' => $b->getBrand(),
                'model' => $b->getModel(),
                'status' => $b->getStatus(),
                'qr_hash' => $b->getQrHash(),
            ], $bikes)]);
        }

        return view('bike/my-bikes', [
            'title' => 'Moje kola – BikeSwap',
            'bikes' => $bikes,
            'currentUser' => $currentUser,
            'session' => $this->session,
        ]);
    }

    /**
     * Set a photo as the primary photo for a bike.
     * POST /bike/{id}/photo/{photoId}/primary
     */
    public function setPrimaryPhoto(Request $request): Response
    {
        $bikeId = (int) $request->param('id');
        $photoId = (int) $request->param('photoId');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/bike/{$bikeId}/edit");
        }

        $bike = $this->bikeRepository->findById($bikeId);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        // Verify the photo belongs to this bike
        $photo = $this->bikeRepository->findPhotoById($photoId);

        if ($photo === null || $photo->getBikeId() !== $bikeId) {
            throw new \RuntimeException('Fotografie nenalezena.', 404);
        }

        $this->bikeRepository->setPrimaryPhoto($photoId, $bikeId);

        $this->session->flash('success', 'Hlavní fotografie byla změněna.');

        return redirect("/bike/{$bikeId}/edit");
    }

    /**
     * Delete a photo from a bike.
     * POST /bike/{id}/photo/{photoId}/delete
     */
    public function deletePhoto(Request $request): Response
    {
        $bikeId = (int) $request->param('id');
        $photoId = (int) $request->param('photoId');

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');

            return redirect("/bike/{$bikeId}/edit");
        }

        $bike = $this->bikeRepository->findById($bikeId);
        $currentUser = $this->authService->currentUser();

        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        if (!$bike->isOwnedBy($currentUser->getId()) && !$currentUser->isAdmin()) {
            throw new \RuntimeException('Nemáte oprávnění.', 403);
        }

        $photo = $this->bikeRepository->findPhotoById($photoId);

        if ($photo === null || $photo->getBikeId() !== $bikeId) {
            throw new \RuntimeException('Fotografie nenalezena.', 404);
        }

        // Delete file from storage
        $this->fileUploadService->delete($photo->getFilePath());

        // Delete record from database
        $this->bikeRepository->deletePhoto($photoId);

        $this->session->flash('success', 'Fotografie byla smazána.');

        return redirect("/bike/{$bikeId}/edit");
    }

    /**
     * Handle multiple file uploads for a bike.
     */
    private function handlePhotoUploads(array $files, int $bikeId, int $uploadedBy): void
    {
        // Normalize files array (PHP's $_FILES structure for multiple files is weird)
        if (isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            $count = count($files['tmp_name']);
            $isFirstBikePhoto = empty($this->bikeRepository->findPhotosByBikeId($bikeId));

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
                    $isPrimary = ($i === 0 && $isFirstBikePhoto);
                    $this->bikeRepository->addPhoto($bikeId, $result['path'], $uploadedBy, $isPrimary);
                }
            }
        } elseif (isset($files['tmp_name']) && $files['error'] === UPLOAD_ERR_OK) {
            // Single file upload
            $result = $this->fileUploadService->uploadBikePhoto($files);

            if ($result['success']) {
                $isFirstBikePhoto = empty($this->bikeRepository->findPhotosByBikeId($bikeId));
                $this->bikeRepository->addPhoto($bikeId, $result['path'], $uploadedBy, $isFirstBikePhoto);
            }
        }
    }
}