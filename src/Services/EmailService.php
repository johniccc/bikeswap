<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Bike;
use App\Repository\UserPreferencesRepository;
use App\Repository\UserRepository;

/**
 * Email service.
 *
 * Sends email notifications using PHP's mail() function.
 * Checks user preferences before sending — respects opt-out settings.
 *
 * NOTE: This is a functional stub. On the school server, mail() may need
 * proper SMTP configuration. For development, emails are logged instead of sent.
 */
class EmailService
{
    private UserRepository $userRepository;
    private UserPreferencesRepository $preferencesRepository;
    private string $fromAddress;
    private string $fromName;
    private string $appUrl;
    private bool $isDebug;

    public function __construct(
        UserRepository $userRepository,
        UserPreferencesRepository $preferencesRepository,
        array $config
    ) {
        $this->userRepository = $userRepository;
        $this->preferencesRepository = $preferencesRepository;
        $this->fromAddress = $config['mail']['from_address'] ?? 'noreply@bikeswap.cz';
        $this->fromName = $config['mail']['from_name'] ?? 'BikeSwap';
        $this->appUrl = rtrim($config['app']['url'] ?? 'http://localhost', '/');
        $this->isDebug = ($config['app']['debug'] ?? false) === true;
    }

    /**
     * Build an absolute URL from a path.
     */
    private function url(string $path): string
    {
        return $this->appUrl . $path;
    }

    /**
     * Notify bike owner that someone found their bike.
     */
    public function sendFoundReportNotification(int $ownerId, Bike $bike, int $reportId): void
    {
        if (!$this->preferencesRepository->isEnabled($ownerId, 'email_on_found_report')) {
            return;
        }

        $user = $this->userRepository->findById($ownerId);

        if ($user === null) {
            return;
        }

        $subject = 'Někdo nalezl vaše kolo – ' . $bike->getFullName();
        $body = sprintf(
            "Dobrý den,\n\nněkdo nahlásil nález vašeho kola %s.\n\n" .
            "Přejděte na BikeSwap a podívejte se na detail nálezu:\n%s\n\n" .
            "S pozdravem,\nBikeSwap",
            $bike->getFullName(),
            $this->url('/dashboard')
        );

        $this->send($user->getEmail(), $subject, $body);
    }

    /**
     * Send confirmation email to the finder with conversation link.
     */
    public function sendFinderConfirmation(string $finderEmail, Bike $bike, string $conversationToken): void
    {
        $subject = 'Potvrzení nahlášení nálezu – BikeSwap';
        $body = sprintf(
            "Dobrý den,\n\nděkujeme za nahlášení nálezu kola %s.\n\n" .
            "Majitel kola byl upozorněn. Pro přístup ke konverzaci použijte tento odkaz:\n%s\n\n" .
            "Tento odkaz si uložte — je to váš jediný přístup ke konverzaci.\n\n" .
            "S pozdravem,\nBikeSwap",
            $bike->getFullName(),
            $this->url('/found/conversation/' . $conversationToken)
        );

        $this->send($finderEmail, $subject, $body);
    }

    /**
     * Notify bike owner about a new message from the finder.
     */
    public function sendMessageNotification(int $ownerId, Bike $bike, int $reportId): void
    {
        if (!$this->preferencesRepository->isEnabled($ownerId, 'email_on_message')) {
            return;
        }

        $user = $this->userRepository->findById($ownerId);

        if ($user === null) {
            return;
        }

        $subject = 'Nová zpráva od nálezce – ' . $bike->getFullName();
        $body = sprintf(
            "Dobrý den,\n\nmáte novou zprávu v konverzaci o kole %s.\n\n" .
            "Přejděte do BikeSwap pro odpověď:\n%s\n\n" .
            "S pozdravem,\nBikeSwap",
            $bike->getFullName(),
            $this->url('/dashboard')
        );

        $this->send($user->getEmail(), $subject, $body);
    }

    /**
     * Send message notification to finder (via email, since they may not be registered).
     */
    public function sendMessageToFinder(string $finderEmail, Bike $bike, string $conversationToken): void
    {
        $subject = 'Nová zpráva od majitele kola – BikeSwap';
        $body = sprintf(
            "Dobrý den,\n\nmajitel kola %s vám odpověděl.\n\n" .
            "Pro zobrazení zprávy a odpověď použijte tento odkaz:\n%s\n\n" .
            "S pozdravem,\nBikeSwap",
            $bike->getFullName(),
            $this->url('/found/conversation/' . $conversationToken)
        );

        $this->send($finderEmail, $subject, $body);
    }

    /**
     * Notify owner about a new reservation request.
     */
    public function sendReservationRequest(int $ownerId, Bike $bike, int $reservationId): void
    {
        if (!$this->preferencesRepository->isEnabled($ownerId, 'email_on_reservation')) {
            return;
        }

        $user = $this->userRepository->findById($ownerId);

        if ($user === null) {
            return;
        }

        $subject = 'Nová žádost o výpůjčku – ' . $bike->getFullName();
        $body = sprintf(
            "Dobrý den,\n\nněkdo požádal o výpůjčku vašeho kola %s.\n\n" .
            "Přejděte do BikeSwap pro schválení nebo zamítnutí:\n%s\n\n" .
            "S pozdravem,\nBikeSwap",
            $bike->getFullName(),
            $this->url('/reservation/' . $reservationId)
        );

        $this->send($user->getEmail(), $subject, $body);
    }

    /**
     * Notify borrower about reservation status change.
     */
    public function sendReservationStatusChange(int $borrowerId, Bike $bike, int $reservationId, string $status, string $statusMessage): void
    {
        if (!$this->preferencesRepository->isEnabled($borrowerId, 'email_on_status_change')) {
            return;
        }

        $user = $this->userRepository->findById($borrowerId);

        if ($user === null) {
            return;
        }

        $subject = $statusMessage . ' – ' . $bike->getFullName();
        $body = sprintf(
            "Dobrý den,\n\n%s\n\n" .
            "Přejděte do BikeSwap pro více informací:\n%s\n\n" .
            "S pozdravem,\nBikeSwap",
            $statusMessage,
            $this->url('/reservation/' . $reservationId)
        );

        $this->send($user->getEmail(), $subject, $body);
    }

    /**
     * Send password reset email. Always sent — no preference check (security email).
     */
    public function sendPasswordReset(string $email, string $resetUrl): void
    {
        $subject = 'Obnovení hesla – BikeSwap';
        $body = sprintf(
            "Dobrý den,\n\npožádali jste o obnovení hesla k vašemu účtu na BikeSwap.\n\n" .
            "Pro nastavení nového hesla klikněte na tento odkaz (platný 1 hodinu):\n%s\n\n" .
            "Pokud jste o obnovení hesla nežádali, tento e-mail ignorujte.\n\n" .
            "S pozdravem,\nBikeSwap",
            $resetUrl
        );

        $this->send($email, $subject, $body);
    }

    /**
     * Notify finder that the owner marked the bike as found/recovered.
     */
    public function sendFoundReportResolved(string $finderEmail, string $bikeName, ?string $conversationToken = null): void
    {
        $subject = 'Nález kola vyřešen – BikeSwap';

        $linkLine = $conversationToken
            ? "\nPro zobrazení konverzace použijte tento odkaz:\n" . $this->url('/found/conversation/' . $conversationToken) . "\n"
            : '';

        $body = sprintf(
            "Dobrý den,\n\nmajitel kola %s označil případ jako vyřešený — kolo bylo nalezeno.\n\n" .
            "Děkujeme vám za pomoc při jeho nahlášení!%s\n" .
            "S pozdravem,\nBikeSwap",
            $bikeName,
            $linkLine
        );

        $this->send($finderEmail, $subject, $body);
    }

    /**
     * Notify user about a new message in reservation conversation.
     */
    public function sendReservationMessage(int $recipientId, Bike $bike, int $reservationId): void
    {
        if (!$this->preferencesRepository->isEnabled($recipientId, 'email_on_message')) {
            return;
        }

        $user = $this->userRepository->findById($recipientId);

        if ($user === null) {
            return;
        }

        $subject = 'Nová zpráva v konverzaci – ' . $bike->getFullName();
        $body = sprintf(
            "Dobrý den,\n\nmáte novou zprávu v konverzaci k rezervaci kola %s.\n\n" .
            "Přejděte do BikeSwap pro odpověď:\n%s\n\n" .
            "S pozdravem,\nBikeSwap",
            $bike->getFullName(),
            $this->url('/reservation/' . $reservationId)
        );

        $this->send($user->getEmail(), $subject, $body);
    }

    /**
     * Notify owner about a bike warning (long-standing bike).
     */
    public function sendBikeWarningNotification(int $ownerId, string $bikeName, string $deadline, string $location): void
    {
        if (!$this->preferencesRepository->isEnabled($ownerId, 'email_on_status_change')) {
            return;
        }

        $user = $this->userRepository->findById($ownerId);
        if ($user === null) {
            return;
        }

        $subject = 'Upozornění na vaše kolo – ' . $bikeName;
        $body = sprintf(
            "Dobrý den,\n\nvaše kolo %s bylo nalezeno na místě: %s.\n\n" .
            "Prosíme, vyzvedněte si ho do %s, jinak bude předáno k dalšímu řízení.\n\n" .
            "S pozdravem,\nBikeSwap",
            $bikeName,
            $location,
            date('d.m.Y', strtotime($deadline))
        );

        $this->send($user->getEmail(), $subject, $body);
    }

    /**
     * Send an email (or log it in debug mode).
     */
    private function send(string $to, string $subject, string $body): void
    {
        $headers = [
            'From' => "{$this->fromName} <{$this->fromAddress}>",
            'Reply-To' => $this->fromAddress,
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Mailer' => 'BikeSwap/1.0',
        ];

        if ($this->isDebug) {
            // In development, just log the email
            error_log(sprintf(
                "[EMAIL] To: %s | Subject: %s | Body: %s",
                $to,
                $subject,
                substr($body, 0, 200)
            ));

            return;
        }

        $headerString = implode("\r\n", array_map(
            fn($k, $v) => "{$k}: {$v}",
            array_keys($headers),
            array_values($headers)
        ));

        mail($to, $subject, $body, $headerString);
    }
}
