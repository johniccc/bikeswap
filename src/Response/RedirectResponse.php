<?php

declare(strict_types=1);

namespace App\Response;

/**
 * Redirect Response - sends the client to a different URL.
 * 
 * Usage in controller:
 *   return new RedirectResponse('/login');
 *   return new RedirectResponse('/bike/5', 301);  // permanent redirect
 */
class RedirectResponse extends Response
{
    public function __construct(string $url, int $statusCode = 302)
    {
        parent::__construct($statusCode);
        $this->withHeader('Location', $url);
    }

    protected function sendBody(): void
    {
        // Redirect responses have no body
    }
}