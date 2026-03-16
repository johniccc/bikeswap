<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\CookieConsentRepository;
use App\Response\Response;

class CookieConsentController
{
    private CookieConsentRepository $consentRepo;
    private Session $session;

    public function __construct(CookieConsentRepository $consentRepo, Session $session)
    {
        $this->consentRepo = $consentRepo;
        $this->session = $session;
    }

    public function store(Request $request): Response
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $consent = (string) ($data['consent'] ?? '');

        if (!in_array($consent, ['all', 'necessary'], true)) {
            return json(['error' => 'Invalid consent value'], 400);
        }

        $sessionId = session_id();
        $userId = $this->session->userId();

        $this->consentRepo->store($sessionId, $userId, $consent, $request->ip());

        return json(['success' => true, 'consent' => $consent]);
    }
}
