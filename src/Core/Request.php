<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request wrapper.
 * 
 * Encapsulates PHP superglobals ($_GET, $_POST, $_SERVER, $_FILES)
 * into a single object with a clean interface.
 */
class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $server;
    private array $files;
    private array $cookies;
    private array $routeParams = [];

    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $server = [],
        array $files = [],
        array $cookies = []
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
        $this->cookies = $cookies;
    }

    /**
     * Create a Request from PHP superglobals.
     */
    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_SERVER,
            $_FILES,
            $_COOKIE
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get a query parameter (?key=value).
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a POST body parameter.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Get all POST body parameters.
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Get a route parameter (e.g. {id} from /bike/{id}).
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Set route parameters (called by Router after matching).
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get uploaded file(s) by form field name.
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get a server variable.
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Check if the client expects a JSON response.
     */
    public function wantsJson(): bool
    {
        $accept = $this->server('HTTP_ACCEPT', '');

        return str_contains($accept, 'application/json');
    }

    /**
     * Get the client IP address.
     */
    public function ip(): string
    {
        return $this->server('HTTP_X_FORWARDED_FOR')
            ?? $this->server('REMOTE_ADDR')
            ?? '0.0.0.0';
    }

    /**
     * Get the User-Agent string.
     */
    public function userAgent(): string
    {
        return $this->server('HTTP_USER_AGENT', '');
    }

    /**
     * Check if the request method matches.
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }
}