<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\TwoFactorRepository;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use OTPHP\TOTP;

/**
 * Two-factor authentication (TOTP) management.
 *
 * Handles generating secrets, verifying codes, managing recovery codes,
 * and enabling/disabling 2FA for user accounts.
 */
class TwoFactorService
{
    private TwoFactorRepository $twoFactorRepo;

    public function __construct(TwoFactorRepository $twoFactorRepo)
    {
        $this->twoFactorRepo = $twoFactorRepo;
    }

    /**
     * Generate a new TOTP secret key.
     *
     * @return string Base32-encoded secret
     */
    public function generateSecret(): string
    {
        $totp = TOTP::generate();
        return $totp->getSecret();
    }

    /**
     * Build the otpauth:// URI for registering in an authenticator app.
     *
     * @param User   $user   User whose email is used as the TOTP label
     * @param string $secret Base32-encoded TOTP secret
     * @return string otpauth:// URI
     */
    public function getProvisioningUri(User $user, string $secret): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('BikeSwap');

        return $totp->getProvisioningUri();
    }

    /**
     * Generate a QR code data URI from a provisioning URI for embedding in HTML.
     *
     * @param string $provisioningUri The otpauth:// URI to encode
     * @return string Base64-encoded PNG data URI
     */
    public function generateQrDataUri(string $provisioningUri): string
    {
        $options = new QROptions([
            'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
            'scale'         => 10,
            'imageBase64'   => true,
            'quietzoneSize' => 2,
        ]);

        $qrCode = new QRCode($options);
        return $qrCode->render($provisioningUri);
    }

    /**
     * Verify a TOTP code against a secret with a +/-1 time-step window.
     *
     * @param string $secret Base32-encoded TOTP secret
     * @param string $code   6-digit code from the authenticator app
     * @return bool True if the code is valid
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $totp = TOTP::createFromSecret($secret);
        return $totp->verify($code, null, 1); // window ±1
    }

    /**
     * Generate a set of 8 single-use recovery codes.
     *
     * @return string[] 8 recovery codes in xxxx-xxxx format
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

        for ($i = 0; $i < 8; $i++) {
            $part1 = '';
            $part2 = '';
            for ($j = 0; $j < 4; $j++) {
                $part1 .= $chars[random_int(0, strlen($chars) - 1)];
                $part2 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $codes[] = $part1 . '-' . $part2;
        }

        return $codes;
    }

    /**
     * Enable 2FA for a user by storing their secret and recovery codes.
     *
     * @param int      $userId User ID
     * @param string   $secret Base32-encoded TOTP secret
     * @param string[] $codes  Plain-text recovery codes (will be hashed before storage)
     * @return void
     */
    public function enable(int $userId, string $secret, array $codes): void
    {
        $this->twoFactorRepo->enableTotp($userId, $secret);
        $this->storeRecoveryCodes($userId, $codes);
    }

    /**
     * Replace all recovery codes for a user with new ones.
     *
     * @param int      $userId User ID
     * @param string[] $codes  Plain-text recovery codes (will be bcrypt-hashed)
     * @return void
     */
    public function storeRecoveryCodes(int $userId, array $codes): void
    {
        $this->twoFactorRepo->deleteRecoveryCodes($userId);

        $hashes = array_map(
            fn(string $code) => password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]),
            $codes
        );

        $this->twoFactorRepo->storeRecoveryCodes($userId, $hashes);
    }

    /**
     * Disable 2FA for a user, removing their secret and all recovery codes.
     *
     * @param int $userId User ID
     * @return void
     */
    public function disable(int $userId): void
    {
        $this->twoFactorRepo->disableTotp($userId);
        $this->twoFactorRepo->deleteRecoveryCodes($userId);
    }

    /**
     * Verify and consume a recovery code for a user.
     *
     * @param int    $userId User ID
     * @param string $code   Plain-text recovery code (e.g. "abcd-1234")
     * @return bool True if a matching unused code was found and consumed
     */
    public function verifyRecoveryCode(int $userId, string $code): bool
    {
        $code = strtolower(trim($code));
        $unusedCodes = $this->twoFactorRepo->getUnusedCodes($userId);

        foreach ($unusedCodes as $row) {
            if (password_verify($code, $row['code_hash'])) {
                $this->twoFactorRepo->markCodeUsed((int) $row['id']);
                return true;
            }
        }

        return false;
    }

    /**
     * Count remaining unused recovery codes for a user.
     *
     * @param int $userId User ID
     * @return int Number of unused recovery codes
     */
    public function countUnusedCodes(int $userId): int
    {
        return $this->twoFactorRepo->countUnusedCodes($userId);
    }
}
