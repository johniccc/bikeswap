<?php

declare(strict_types=1);

/**
 * Route Definitions
 * 
 * IMPORTANT: More specific routes must come before parametric ones.
 * e.g. /bike/new MUST be before /bike/{hash}
 */

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\BikeController;
use App\Controllers\FileController;
use App\Middleware\AuthMiddleware;

// ── Public routes ──────────────────────────────────────────────

$router->get('/', [HomeController::class, 'index']);

// ── File serving (images, QR codes) ───────────────────────────

$router->get('/file/bike-photo/{id}', [FileController::class, 'bikePhoto']);
$router->get('/file/qr/{hash}', [FileController::class, 'qrCode']);

// ── Auth routes ────────────────────────────────────────────────

$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// ── Authenticated routes ───────────────────────────────────────

$router->group('', [AuthMiddleware::class], function ($router) {
    // Dashboard
    $router->get('/dashboard', [BikeController::class, 'myBikes']);

    // Bike CRUD (BEFORE the public /bike/{hash} route!)
    $router->get('/bike/new', [BikeController::class, 'createForm']);
    $router->post('/bike/new', [BikeController::class, 'store']);
    $router->get('/bike/{id}/edit', [BikeController::class, 'editForm']);
    $router->post('/bike/{id}/edit', [BikeController::class, 'update']);
    $router->post('/bike/{id}/delete', [BikeController::class, 'delete']);
});

// ── Public bike detail (QR code scan) ──────────────────────────
// Must be AFTER /bike/new to avoid matching "new" as a hash
$router->get('/bike/{hash}', [BikeController::class, 'publicDetail']);

// ── Admin routes ───────────────────────────────────────────────

// $router->group('/admin', [AuthMiddleware::class], function ($router) {
//     $router->get('/dashboard', [AdminController::class, 'dashboard']);
// });