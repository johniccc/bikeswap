<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\BikeRepository;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR Code service.
 * 
 * Generates unique QR hashes and QR code images for bikes.
 * QR code links to the public bike page where anyone can see basic info
 * or report a found bike.
 */
class QRService
{
    private BikeRepository $bikeRepository;
    private int $hashLength;
    private string $appUrl;

    public function __construct(BikeRepository $bikeRepository, array $config)
    {
        $this->bikeRepository = $bikeRepository;
        $this->hashLength = $config['qr']['hash_length'] ?? 16;
        $this->appUrl = rtrim($config['app']['url'] ?? '', '/');
    }

    /**
     * Generate a unique hash for a new bike.
     * Checks for collisions in the database.
     */
    public function generateUniqueHash(): string
    {
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $hash = bin2hex(random_bytes($this->hashLength));

            if (!$this->bikeRepository->qrHashExists($hash)) {
                return $hash;
            }
        }

        throw new \RuntimeException('Failed to generate a unique QR hash after multiple attempts.');
    }

    /**
     * Get the public URL that a QR code should link to.
     */
    public function getBikeUrl(string $qrHash): string
    {
        return $this->appUrl . '/bike/' . $qrHash;
    }

    /**
     * Generate a QR code image as PNG binary data.
     */
    public function generateQrImage(string $qrHash): string
    {
        $url = $this->getBikeUrl($qrHash);

        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'scale'        => 10,
            'imageBase64'  => false,
            'quietzoneSize' => 2,
        ]);

        $qrCode = new QRCode($options);

        return $qrCode->render($url);
    }

    /**
     * Generate a QR code as base64-encoded data URI (for embedding in HTML).
     */
    public function generateQrDataUri(string $qrHash): string
    {
        $url = $this->getBikeUrl($qrHash);

        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'scale'        => 10,
            'imageBase64'  => true,
            'quietzoneSize' => 2,
        ]);

        $qrCode = new QRCode($options);

        return $qrCode->render($url);
    }
}