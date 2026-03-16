<?php

declare(strict_types=1);

namespace App\Services;

class TurnstileService
{
    private string $secretKey;

    public function __construct(array $config)
    {
        $this->secretKey = $config['turnstile']['secret_key'] ?? '';
    }

    /**
     * Verify a Turnstile CAPTCHA token.
     *
     * @return string|null Null on success, error message on failure.
     */
    public function verify(string $token): ?string
    {
        if ($this->secretKey === '') {
            return null;
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => $this->secretKey,
                'response' => $token,
            ]),
        ]);
        $result = curl_exec($ch);
        $curlError = curl_errno($ch);
        curl_close($ch);

        if ($curlError !== 0 || $result === false) {
            return 'Ověření CAPTCHA selhalo (chyba spojení). Zkuste to znovu.';
        }

        $data = json_decode($result, true) ?? [];
        if (!($data['success'] ?? false)) {
            return 'Ověření CAPTCHA selhalo. Zkuste to znovu.';
        }

        return null;
    }
}
