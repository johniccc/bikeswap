<?php

declare(strict_types=1);

/**
 * Application Configuration
 * 
 * This file contains application settings that are NOT secrets.
 * Secrets (DB password, API keys) belong in .env
 * 
 * Values from .env can be referenced here via $_ENV.
 */
return [

    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'BikeSwap',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'bikeswap',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
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
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@bikeswap.cz',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'BikeSwap',
    ],

    'turnstile' => [
        'site_key' => $_ENV['TURNSTILE_SITE_KEY'] ?? '',
        'secret_key' => $_ENV['TURNSTILE_SECRET_KEY'] ?? '',
    ],

];