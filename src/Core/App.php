<?php

declare(strict_types=1);

namespace App\Core;

use App\Response\Response;

/**
 * Main application class.
 * 
 * Responsible for bootstrapping the application,
 * routing the request and sending the response.
 */
class App
{
    private Router $router;
    private Container $container;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->container = new Container($config);
        $this->router = new Router($this->container);

        $this->registerSingletons();
        $this->registerRoutes();
    }

    /**
     * Handle the incoming request and send a response.
     */
    public function run(): void
    {
        // Start session for every request
        $session = $this->container->make(Session::class);
        $session->start();

        $request = Request::capture();

        try {
            $response = $this->router->dispatch($request);
        } catch (\Throwable $e) {
            $response = $this->handleException($e, $request);
        }

        $response->send();
    }

    /**
     * Register services that should only be instantiated once.
     */
    private function registerSingletons(): void
    {
        $this->container->singleton(Database::class, function (Container $c) {
            return new Database($this->config);
        });

        $this->container->singleton(Session::class, function (Container $c) {
            return new Session($this->config);
        });
    }

    /**
     * Load route definitions.
     */
    private function registerRoutes(): void
    {
        $router = $this->router;
        require __DIR__ . '/../../config/routes.php';
    }

    /**
     * Convert exceptions to appropriate error responses.
     */
    private function handleException(\Throwable $e, Request $request): Response
    {
        $code = $e->getCode() >= 400 && $e->getCode() < 600
            ? $e->getCode()
            : 500;

        // Log the error
        error_log(sprintf(
            "[%s] %s in %s:%d\n%s",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        // Try to get session so error pages render within the main layout (nav, footer)
        $session = null;
        try {
            $session = $this->container->make(Session::class);
        } catch (\Throwable) {
            // Session might not be available during early bootstrap errors
        }

        if ($request->wantsJson()) {
            return json(
                ['error' => $this->isDebug() ? $e->getMessage() : 'Internal Server Error'],
                $code
            );
        }

        return view(
            "errors/{$code}",
            [
                'message' => $this->isDebug() ? $e->getMessage() : 'Něco se pokazilo.',
                'code' => $code,
                'session' => $session,
            ],
            $code
        )->withLayout('layouts/public');
    }

    private function isDebug(): bool
    {
        return ($this->config['app']['debug'] ?? false) === true;
    }
}