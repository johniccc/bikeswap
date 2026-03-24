<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Response\Response;

/**
 * Validates CSRF tokens on state-changing requests (POST, PUT, DELETE, PATCH).
 *
 * On failure, redirects back to the referring page with an error flash message,
 * or returns a JSON error for API requests.
 */
class CsrfMiddleware
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Validate the CSRF token on state-changing HTTP methods.
     *
     * @return Response|null Return a Response to short-circuit, null to continue.
     */
    public function handle(Request $request): ?Response
    {
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return null;
        }

        if ($this->session->validateCsrf($request->input('_csrf', ''))) {
            return null;
        }

        if ($request->wantsJson()) {
            return json(['error' => 'Neplatný bezpečnostní token.'], 403);
        }

        $this->session->flash('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');

        $referer = $request->server('HTTP_REFERER');
        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH) ?: '/';
            return redirect($path);
        }

        return redirect('/');
    }
}
