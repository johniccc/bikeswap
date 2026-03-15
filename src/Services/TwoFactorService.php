<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\TwoFactorRepository;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use OTPHP\TOTP;

class TwoFactorService
{
    private TwoFactorRepository $twoFactorRepo;

    public function __construct(TwoFactorRepository $twoFactorRepo)
    {
        $this->twoFactorRepo = $twoFactorRepo;
    }

    public function generateSecret(): string
    {
        $totp = TOTP::generate();
        return $totp->getSecret();
    }

    public function getProvisioningUri(User $user, string $secret): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('BikeSwap');

        return $totp->getProvisioningUri();
    }

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

    public function verifyCode(string $secret, string $code): bool
    {
        $totp = TOTP::createFromSecret($secret);
        return $totp->verify($code, null, 1); // window ±1
    }

    /**
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

    public function enable(int $userId, string $secret, array $codes): void
    {
        $this->twoFactorRepo->enableTotp($userId, $secret);
        $this->storeRecoveryCodes($userId, $codes);
    }

    public function storeRecoveryCodes(int $userId, array $codes): void
    {
        $this->twoFactorRepo->deleteRecoveryCodes($userId);

        $hashes = array_map(
            fn(string $code) => password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]),
            $codes
        );

        $this->twoFactorRepo->storeRecoveryCodes($userId, $hashes);
    }

    public function disable(int $userId): void
    {
        $this->twoFactorRepo->disableTotp($userId);
        $this->twoFactorRepo->deleteRecoveryCodes($userId);
    }

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

    public function countUnusedCodes(int $userId): int
    {
        return $this->twoFactorRepo->countUnusedCodes($userId);
    }
}
