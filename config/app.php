<?php

declare(strict_types=1);

/**
 * Application Configuration
 * 
 * This file contains application settings that are NOT secrets.
 * Secrets (DB password, API keys) belong in .env
 * 
 * Uses the env() helper from functions.php for consistent access.
 */
return [

    'app' => [
        'name' => env('APP_NAME', 'BikeSwap'),
        'url' => env('APP_URL', 'http://localhost'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'name' => env('DB_NAME', 'bikeswap'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5 MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'bikes_dir' => __DIR__ . '/../storage/uploads/bikes',
        'reports_dir' => __DIR__ . '/../storage/uploads/reports',
    ],

    'rate_limit' => [
        'max_attempts' => 5,
        'window_seconds' => 3600, // 1 hour
    ],

    'session' => [
        'lifetime' => 3600, // 1 hour
        'name' => 'bikeswap_session',
    ],

    'qr' => [
        'hash_length' => 16, // bytes, will be hex-encoded to 32 chars
    ],

    'mail' => [
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@bikeswap.cz'),
        'from_name' => env('MAIL_FROM_NAME', 'BikeSwap'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
    ],

];