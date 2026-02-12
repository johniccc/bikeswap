<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repository\BikeRepository;
use App\Response\FileResponse;
use App\Response\Response;
use App\Services\FileUploadService;
use App\Services\QRService;

/**
 * File Controller.
 * 
 * Serves uploaded files from storage/ through PHP.
 * This allows access control — files are NOT in public/.
 */
class FileController
{
    private BikeRepository $bikeRepository;
    private FileUploadService $fileUploadService;
    private QRService $qrService;

    public function __construct(
        BikeRepository $bikeRepository,
        FileUploadService $fileUploadService,
        QRService $qrService
    ) {
        $this->bikeRepository = $bikeRepository;
        $this->fileUploadService = $fileUploadService;
        $this->qrService = $qrService;
    }

    /**
     * Serve a bike photo by photo ID.
     * GET /file/bike-photo/{id}
     */
    public function bikePhoto(Request $request): Response
    {
        $photoId = (int) $request->param('id');
        $photo = $this->bikeRepository->findPhotoById($photoId);

        if ($photo === null) {
            throw new \RuntimeException('Photo not found', 404);
        }

        return $this->serveFile($photo->getFilePath());
    }

    /**
     * Serve a QR code image for a bike.
     * GET /file/qr/{hash}
     */
    public function qrCode(Request $request): Response
    {
        $hash = $request->param('hash');
        $bike = $this->bikeRepository->findByQrHash($hash);

        if ($bike === null) {
            throw new \RuntimeException('Bike not found', 404);
        }

        $imageData = $this->qrService->generateQrImage($hash);

        return new FileResponse($imageData, 'image/png', "qr-{$hash}.png");
    }

    /**
     * Serve a file from storage, with proper headers.
     */
    private function serveFile(string $relativePath): Response
    {
        $mimeType = $this->fileUploadService->getMimeType($relativePath);
        $fullPath = $this->fileUploadService->getFullPath($relativePath);

        if ($fullPath === null || $mimeType === null) {
            throw new \RuntimeException('File not found', 404);
        }

        $content = file_get_contents($fullPath);

        return new FileResponse($content, $mimeType);
    }
}