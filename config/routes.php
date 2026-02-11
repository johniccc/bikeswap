<?php

declare(strict_types=1);

/**
 * Route Definitions
 * 
 * $router is passed in from App::registerRoutes().
 */

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

// ── Public routes ──────────────────────────────────────────────

$router->get('/', [HomeController::class, 'index']);

// ── Auth routes ────────────────────────────────────────────────

$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// ── Authenticated routes ───────────────────────────────────────

// $router->group('', [AuthMiddleware::class], function ($router) {
//     $router->get('/dashboard', [HomeController::class, 'dashboard']);
// });

// ── Admin routes ───────────────────────────────────────────────

// $router->group('/admin', [AuthMiddleware::class], function ($router) {
//     $router->get('/dashboard', [AdminController::class, 'dashboard']);
// });