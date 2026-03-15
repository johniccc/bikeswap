<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Response\Response;
use App\Services\AuthService;

/**
 * Authentication middleware.
 *
 * Blocks unauthenticated users from accessing protected routes.
 * Also blocks banned users (forces logout).
 * Returns a redirect to /login (HTML) or 401 (JSON).
 */
class AuthMiddleware
{
    private Session $session;
    private AuthService $authService;

    public function __construct(Session $session, AuthService $authService)
    {
        $this->session = $session;
        $this->authService = $authService;
    }

    /**
     * Handle the request.
     *
     * @return Response|null  Return a Response to short-circuit, null to continue.
     */
    public function handle(Request $request): ?Response
    {
        if ($this->session->isLoggedIn()) {
            // Check if user is banned
            $user = $this->authService->currentUser();
            if ($user !== null && $user->isBanned()) {
                $this->authService->logout();
                $this->session->flash('error', 'Váš účet byl zablokován.');
                return redirect('/login');
            }

            return null; // Continue to controller
        }

        if ($request->wantsJson()) {
            return json(['error' => 'Vyžadováno přihlášení.'], 401);
        }

        $this->session->flash('error', 'Pro přístup se musíte přihlásit.');

        // Don't save polling/API endpoints as redirect targets
        $path = $request->getPath();
        if (preg_match('#/(count|poll)$#', $path)) {
            return redirect('/login');
        }

        return redirect('/login?redirect=' . urlencode($path));
    }
}
