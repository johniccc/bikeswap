<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session wrapper.
 * 
 * Encapsulates PHP's native session handling into a clean OOP interface.
 * Handles session start, flash messages, and CSRF token generation.
 */
class Session
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config['session'] ?? [];
    }

    /**
     * Start the session if not already active.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = $this->config['lifetime'] ?? 3600;
        $name = $this->config['name'] ?? 'bikeswap_session';

        session_name($name);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        // Expire old sessions
        if (isset($_SESSION['_last_activity'])) {
            if (time() - $_SESSION['_last_activity'] > $lifetime) {
                $this->destroy();
                session_start();
            }
        }

        $_SESSION['_last_activity'] = time();
    }

    /**
     * Get a session value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();

        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists.
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();

        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value.
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();

        unset($_SESSION[$key]);
    }

    /**
     * Destroy the entire session.
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Regenerate the session ID (call after login to prevent session fixation).
     */
    public function regenerate(): void
    {
        $this->ensureStarted();

        session_regenerate_id(true);
    }

    // ── Flash messages ─────────────────────────────────────────

    /**
     * Set a flash message (available only on the next request).
     * 
     * Usage:
     *   $session->flash('success', 'Kolo bylo úspěšně zaregistrováno.');
     */
    public function flash(string $key, string $message): void
    {
        $this->ensureStarted();

        $_SESSION['_flash'][$key] = $message;
    }

    /**
     * Get and remove a flash message.
     */
    public function getFlash(string $key): ?string
    {
        $this->ensureStarted();

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $message;
    }

    /**
     * Check if a flash message exists.
     */
    public function hasFlash(string $key): bool
    {
        $this->ensureStarted();

        return isset($_SESSION['_flash'][$key]);
    }

    // ── CSRF Protection ────────────────────────────────────────

    /**
     * Generate or retrieve the CSRF token for the current session.
     */
    public function csrfToken(): string
    {
        $this->ensureStarted();

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Validate a submitted CSRF token against the session token.
     */
    public function validateCsrf(string $token): bool
    {
        return hash_equals($this->csrfToken(), $token);
    }

    // ── Old Input (form re-population) ─────────────────────────

    /** Fields that must never be stored as old input (security). */
    private const OLD_INPUT_BLACKLIST = [
        'password', 'current_password', 'new_password', 'new_password_confirmation',
        '_csrf', 'cf-turnstile-response',
    ];

    /**
     * Store submitted form data for re-populating fields on the next request.
     * Sensitive fields (passwords, CSRF tokens) are automatically excluded.
     */
    public function setOldInput(array $data): void
    {
        $this->ensureStarted();

        $filtered = array_diff_key($data, array_flip(self::OLD_INPUT_BLACKLIST));
        $_SESSION['_old_input'] = $filtered;
    }

    /**
     * Retrieve and clear all old input (flash behavior).
     */
    public function getOldInput(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        $value = $_SESSION['_old_input'][$key] ?? $default;
        unset($_SESSION['_old_input'][$key]);

        return $value;
    }

    /**
     * Peek at old input without consuming it.
     */
    public function peekOldInput(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        return $_SESSION['_old_input'][$key] ?? $default;
    }

    /**
     * Clear all stored old input.
     */
    public function clearOldInput(): void
    {
        $this->ensureStarted();

        unset($_SESSION['_old_input']);
    }

    // ── Auth helpers ───────────────────────────────────────────

    /**
     * Get the currently logged-in user's ID, or null.
     */
    public function userId(): ?int
    {
        return $this->get('user_id');
    }

    /**
     * Check if a user is logged in.
     */
    public function isLoggedIn(): bool
    {
        return $this->userId() !== null;
    }

    /**
     * Log in a user (store their ID in the session).
     */
    public function login(int $userId): void
    {
        $this->regenerate();
        $this->set('user_id', $userId);
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        $this->destroy();
    }

    /**
     * Ensure session is started before any operation.
     */
    private function ensureStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }
    }
}