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

if (!function_exists('formatDate')) {
    /**
     * Format a date value as dd/mm/yyyy.
     */
    function formatDate(mixed $d): string
    {
        if ($d === null || $d === '') return '';
        if ($d instanceof \DateTimeInterface) return $d->format('d/m/Y');
        $ts = is_int($d) ? $d : strtotime((string)$d);
        return $ts !== false ? date('d/m/Y', $ts) : (string)$d;
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Format a date-time value as dd/mm/yyyy H:i.
     */
    function formatDateTime(mixed $d): string
    {
        if ($d === null || $d === '') return '';
        if ($d instanceof \DateTimeInterface) return $d->format('d/m/Y H:i');
        $ts = is_int($d) ? $d : strtotime((string)$d);
        return $ts !== false ? date('d/m/Y H:i', $ts) : (string)$d;
    }
}

if (!function_exists('isActiveRoute')) {
    /**
     * Return 'active' if the given path matches the current request URI.
     * Used in templates to highlight the current nav item.
     */
    function isActiveRoute(string $path): string
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if ($path === '/' && $current === '/') return 'active';
        if ($path !== '/') {
            $next = $current[strlen($path)] ?? '';
            if (str_starts_with($current, $path) && ($next === '' || $next === '/' || $next === '?')) {
                return 'active';
            }
        }
        return '';
    }
}