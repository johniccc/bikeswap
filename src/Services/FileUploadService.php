<?php

declare(strict_types=1);

namespace App\Services;

/**
 * File upload service.
 * 
 * Handles uploading, validating, and deleting files.
 * Files are stored in storage/uploads/ (outside public/) for security.
 */
class FileUploadService
{
    private int $maxSize;
    private array $allowedTypes;
    private string $bikesDir;
    private string $reportsDir;

    public function __construct(array $config)
    {
        $uploadConfig = $config['upload'];

        $this->maxSize      = $uploadConfig['max_size'];
        $this->allowedTypes = $uploadConfig['allowed_types'];
        $this->bikesDir     = $uploadConfig['bikes_dir'];
        $this->reportsDir   = $uploadConfig['reports_dir'];
    }

    /**
     * Upload a bike photo.
     * 
     * @param array $file  The $_FILES entry (tmp_name, name, size, type, error)
     * @return array{success: bool, path?: string, error?: string}
     */
    public function uploadBikePhoto(array $file): array
    {
        return $this->upload($file, $this->bikesDir);
    }

    /**
     * Upload a found report photo.
     */
    public function uploadReportPhoto(array $file): array
    {
        return $this->upload($file, $this->reportsDir);
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $filePath): bool
    {
        $fullPath = $this->resolveFullPath($filePath);

        if ($fullPath !== null && file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Get the full filesystem path for serving a file.
     * Returns null if the file doesn't exist or path is suspicious.
     */
    public function getFullPath(string $relativePath): ?string
    {
        return $this->resolveFullPath($relativePath);
    }

    /**
     * Get MIME type of a stored file.
     */
    public function getMimeType(string $filePath): ?string
    {
        $fullPath = $this->resolveFullPath($filePath);

        if ($fullPath === null || !file_exists($fullPath)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($fullPath) ?: null;
    }

    /**
     * Normalize PHP's $_FILES array for multiple uploads into an array of single-file arrays.
     * Handles both multi-file (tmp_name is array) and single-file uploads.
     *
     * @return array[] Each element is a single-file array with tmp_name, name, size, type, error.
     */
    public function normalizeFileArray(array $files): array
    {
        $normalized = [];

        if (isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            $count = count($files['tmp_name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $normalized[] = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name'     => $files['name'][$i],
                    'size'     => $files['size'][$i],
                    'type'     => $files['type'][$i],
                    'error'    => $files['error'][$i],
                ];
            }
        } elseif (isset($files['tmp_name']) && ($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $normalized[] = $files;
        }

        return $normalized;
    }

    /**
     * Core upload logic.
     */
    private function upload(array $file, string $targetDir): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadError($file['error'])];
        }

        // Validate size
        if ($file['size'] > $this->maxSize) {
            $maxMb = round($this->maxSize / 1024 / 1024, 1);

            return ['success' => false, 'error' => "Soubor je příliš velký (max {$maxMb} MB)."];
        }

        // Validate MIME type (check actual file content, not just extension)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedTypes, true)) {
            return ['success' => false, 'error' => 'Nepodporovaný formát souboru. Povolené: JPG, PNG, WebP.'];
        }

        // Generate unique filename (prevents collisions and hides original name)
        $extension = $this->mimeToExtension($mimeType);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        // Organize into year/month subdirectories
        $subDir = date('Y/m');
        $fullDir = $targetDir . '/' . $subDir;

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $fullPath = $fullDir . '/' . $filename;

        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'error' => 'Nahrávání souboru selhalo.'];
        }

        // Return relative path (stored in database)
        $relativePath = $subDir . '/' . $filename;

        return ['success' => true, 'path' => $relativePath];
    }

    /**
     * Resolve a relative file path to full filesystem path.
     * Prevents directory traversal attacks.
     */
    private function resolveFullPath(string $relativePath): ?string
    {
        // Prevent directory traversal
        if (str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            return null;
        }

        // Try bikes directory first, then reports
        $bikePath = $this->bikesDir . '/' . $relativePath;
        if (file_exists($bikePath)) {
            return realpath($bikePath) ?: null;
        }

        $reportPath = $this->reportsDir . '/' . $relativePath;
        if (file_exists($reportPath)) {
            return realpath($reportPath) ?: null;
        }

        return null;
    }

    /**
     * Convert MIME type to file extension.
     */
    private function mimeToExtension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'bin',
        };
    }

    /**
     * Get human-readable upload error message.
     */
    private function getUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'Soubor překračuje limit serveru.',
            UPLOAD_ERR_FORM_SIZE  => 'Soubor překračuje limit formuláře.',
            UPLOAD_ERR_PARTIAL    => 'Soubor byl nahrán jen částečně.',
            UPLOAD_ERR_NO_FILE    => 'Nebyl nahrán žádný soubor.',
            UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasný adresář na serveru.',
            UPLOAD_ERR_CANT_WRITE => 'Nepodařilo se zapsat soubor na disk.',
            default               => 'Neznámá chyba při nahrávání.',
        };
    }
}