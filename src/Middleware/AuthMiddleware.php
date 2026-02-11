<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Response\JsonResponse;
use App\Response\RedirectResponse;
use App\Response\Response;

/**
 * Authentication middleware.
 * 
 * Blocks unauthenticated users from accessing protected routes.
 * Returns a redirect to /login (HTML) or 401 (JSON).
 */
class AuthMiddleware
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Handle the request.
     * 
     * @return Response|null  Return a Response to short-circuit, null to continue.
     */
    public function handle(Request $request): ?Response
    {
        if ($this->session->isLoggedIn()) {
            return null; // Continue to controller
        }

        if ($request->wantsJson()) {
            return new JsonResponse(['error' => 'Vyžadováno přihlášení.'], 401);
        }

        $this->session->flash('error', 'Pro přístup se musíte přihlásit.');

        return new RedirectResponse('/login');
    }
}