<?php

declare(strict_types=1);

namespace App\Response;

/**
 * Response that sends raw file content with appropriate headers.
 * 
 * Used by FileController to serve uploaded images and generated QR codes
 * from storage/ through PHP (instead of direct public access).
 * 
 * Usage:
 *   return new FileResponse($imageData, 'image/png', 'qr-code.png');
 */
class FileResponse extends Response
{
    private string $content;
    private string $mimeType;
    private ?string $filename;

    public function __construct(string $content, string $mimeType, ?string $filename = null, int $statusCode = 200)
    {
        parent::__construct($statusCode);
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->filename = $filename;

        $this->withHeader('Content-Type', $mimeType);
        $this->withHeader('Content-Length', (string) strlen($content));
        $this->withHeader('Cache-Control', 'public, max-age=86400'); // 24h cache

        if ($filename !== null) {
            $this->withHeader('Content-Disposition', "inline; filename=\"{$filename}\"");
        }
    }

    protected function sendBody(): void
    {
        echo $this->content;
    }
}