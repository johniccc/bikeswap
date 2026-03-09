<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Response\Response;
use App\Services\AuthService;

class AdminMiddleware
{
    private Session $session;
    private AuthService $authService;

    public function __construct(Session $session, AuthService $authService)
    {
        $this->session = $session;
        $this->authService = $authService;
    }

    public function handle(Request $request): ?Response
    {
        if (!$this->session->isLoggedIn()) {
            if ($request->wantsJson()) {
                return json(['error' => 'Vyžadováno přihlášení.'], 401);
            }
            $this->session->flash('error', 'Pro přístup se musíte přihlásit.');
            return redirect('/login?redirect=' . urlencode($request->getPath()));
        }

        $user = $this->authService->currentUser();

        if ($user === null || !$user->hasRole('police')) {
            if ($request->wantsJson()) {
                return json(['error' => 'Přístup odepřen.'], 403);
            }
            throw new \RuntimeException('Nemáte oprávnění pro přístup do administrace.', 403);
        }

        return null;
    }
}
