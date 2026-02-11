<?php

declare(strict_types=1);

namespace App\Core;

use App\Response\Response;

/**
 * HTTP Router.
 * 
 * Matches incoming requests to controller actions.
 * Supports route parameters ({id}), middleware, and route grouping.
 */
class Router
{
    private Container $container;

    /** @var array<array{method: string, pattern: string, handler: array, middleware: array}> */
    private array $routes = [];

    /** @var string[] Middleware stack for current group */
    private array $groupMiddleware = [];

    /** @var string Prefix for current group */
    private string $groupPrefix = '';

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a GET route.
     */
    public function get(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Group routes with shared prefix and/or middleware.
     * 
     * Usage:
     *   $router->group('/admin', [RoleMiddleware::class], function ($router) {
     *       $router->get('/dashboard', [AdminController::class, 'dashboard']);
     *   });
     */
    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . $prefix;
        $this->groupMiddleware = array_merge($previousMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Dispatch a request to the matching route.
     * 
     * @throws \RuntimeException If no route matches (404) or method not allowed (405).
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $methodNotAllowed = false;

        foreach ($this->routes as $route) {
            $params = $this->matchPath($route['pattern'], $path);

            if ($params === null) {
                continue;
            }

            if ($route['method'] !== $method) {
                $methodNotAllowed = true;
                continue;
            }

            $request->setRouteParams($params);

            return $this->executeRoute($route, $request);
        }

        if ($methodNotAllowed) {
            throw new \RuntimeException('Method Not Allowed', 405);
        }

        throw new \RuntimeException('Not Found', 404);
    }

    /**
     * Register a route.
     */
    private function addRoute(string $method, string $path, array $handler, array $middleware): self
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $this->groupPrefix . $path,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];

        return $this;
    }

    /**
     * Try to match a route pattern against a request path.
     * 
     * Returns an array of matched parameters on success, null on failure.
     * Example: pattern "/bike/{id}" with path "/bike/42" returns ['id' => '42']
     */
    private function matchPath(string $pattern, string $path): ?array
    {
        // Convert route pattern to regex
        // {param} becomes a named capture group (?P<param>[^/]+)
        $regex = preg_replace(
            '/\{([a-zA-Z_]+)\}/',
            '(?P<$1>[^/]+)',
            $pattern
        );

        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        // Extract only named parameters (filter out numeric keys)
        return array_filter(
            $matches,
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Execute middleware chain, then the controller action.
     */
    private function executeRoute(array $route, Request $request): Response
    {
        // Run middleware
        foreach ($route['middleware'] as $middlewareClass) {
            $middleware = $this->container->make($middlewareClass);
            $result = $middleware->handle($request);

            // If middleware returns a Response, short-circuit (e.g. redirect to login)
            if ($result instanceof Response) {
                return $result;
            }
        }

        // Resolve and call the controller
        [$controllerClass, $action] = $route['handler'];
        $controller = $this->container->make($controllerClass);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException(
                "Action '{$action}' not found on controller '{$controllerClass}'",
                500
            );
        }

        return $controller->$action($request);
    }
}