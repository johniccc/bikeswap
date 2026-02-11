<?php

declare(strict_types=1);

/**
 * Global helper functions.
 * Autoloaded via Composer's "files" autoload.
 */

if (!function_exists('e')) {
    /**
     * Shorthand for htmlspecialchars — used in templates.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable with a default fallback.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    /**
     * Shorthand for creating a RedirectResponse.
     */
    function redirect(string $url, int $code = 302): \App\Response\RedirectResponse
    {
        return new \App\Response\RedirectResponse($url, $code);
    }
}

if (!function_exists('view')) {
    /**
     * Shorthand for creating a ViewResponse.
     */
    function view(string $template, array $data = [], int $code = 200): \App\Response\ViewResponse
    {
        return new \App\Response\ViewResponse($template, $data, $code);
    }
}

if (!function_exists('json')) {
    /**
     * Shorthand for creating a JsonResponse.
     */
    function json(mixed $data, int $code = 200): \App\Response\JsonResponse
    {
        return new \App\Response\JsonResponse($data, $code);
    }
}