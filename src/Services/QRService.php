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

    public function __construct(BikeRepository $bikeRepository, array $config)
    {
        $this->bikeRepository = $bikeRepository;
        $this->hashLength = $config['qr']['hash_length'] ?? 16;
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

        throw new \RuntimeException('Nepodařilo se vygenerovat unikátní QR hash po několika pokusech.', 500);
    }

    /**
     * Get the domain-independent QR payload for a bike.
     */
    public function getBikeQrPayload(string $qrHash): string
    {
        return 'bikeswap:' . $qrHash;
    }

    /**
     * Generate a QR code image as PNG binary data with "BikeSwap" branding.
     */
    public function generateQrImage(string $qrHash): string
    {
        $payload = $this->getBikeQrPayload($qrHash);

        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'scale'        => 10,
            'imageBase64'  => false,
            'quietzoneSize' => 2,
        ]);

        $qrCode = new QRCode($options);
        $qrPng = $qrCode->render($payload);

        return $this->addBranding($qrPng);
    }

    /**
     * Generate a QR code as base64-encoded data URI (for embedding in HTML) with "BikeSwap" branding.
     */
    public function generateQrDataUri(string $qrHash): string
    {
        $payload = $this->getBikeQrPayload($qrHash);

        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'scale'        => 10,
            'imageBase64'  => false,
            'quietzoneSize' => 2,
        ]);

        $qrCode = new QRCode($options);
        $qrPng = $qrCode->render($payload);
        $branded = $this->addBranding($qrPng);

        return 'data:image/png;base64,' . base64_encode($branded);
    }

    /**
     * Add "BikeSwap" text below the QR code image.
     */
    private function addBranding(string $qrPng): string
    {
        $qrImage = imagecreatefromstring($qrPng);
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        $text = 'BikeSwap';
        $scale = 4;
        $gdFont = 5; // largest built-in GD font
        $srcFontW = imagefontwidth($gdFont);
        $srcFontH = imagefontheight($gdFont);
        $srcTextW = $srcFontW * strlen($text);

        // Render text at native size on a small temporary image
        $tmpImg = imagecreatetruecolor($srcTextW, $srcFontH);
        $tmpWhite = imagecolorallocate($tmpImg, 255, 255, 255);
        $tmpBlack = imagecolorallocate($tmpImg, 0, 0, 0);
        imagefill($tmpImg, 0, 0, $tmpWhite);
        imagestring($tmpImg, $gdFont, 0, 0, $text, $tmpBlack);

        // Scale up the text
        $scaledW = $srcTextW * $scale;
        $scaledH = $srcFontH * $scale;
        $padding = 16;
        $totalHeight = $qrHeight + $scaledH + $padding * 2;

        $canvas = imagecreatetruecolor($qrWidth, $totalHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopy($canvas, $qrImage, 0, 0, 0, 0, $qrWidth, $qrHeight);

        $textX = (int)(($qrWidth - $scaledW) / 2);
        $textY = $qrHeight + $padding;
        imagecopyresampled($canvas, $tmpImg, $textX, $textY, 0, 0, $scaledW, $scaledH, $srcTextW, $srcFontH);

        ob_start();
        imagepng($canvas);
        $result = ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($tmpImg);
        imagedestroy($canvas);

        return $result;
    }
}