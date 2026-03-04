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
use App\Controllers\TheftController;
use App\Controllers\FoundReportController;
use App\Controllers\MessagePollController;
use App\Controllers\NotificationController;
use App\Controllers\ReservationController;
use App\Controllers\ProfileController;

use App\Middleware\AuthMiddleware;

// ── Public routes ──────────────────────────────────────────────

$router->get('/', [HomeController::class, 'index']);
$router->get('/stolen', [TheftController::class, 'publicList']);
$router->get('/shared', [ReservationController::class, 'sharedBikes']);

// ── File serving (images, QR codes) ───────────────────────────

$router->get('/file/bike-photo/{id}', [FileController::class, 'bikePhoto']);
$router->get('/file/qr/{hash}', [FileController::class, 'qrCode']);

// ── Auth routes ────────────────────────────────────────────────

$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// ── Found reports (public — no auth required) ──────────────────

$router->get('/found/report/{qrHash}', [FoundReportController::class, 'reportForm']);
$router->post('/found/report/{qrHash}', [FoundReportController::class, 'report']);
$router->get('/found/conversation/{token}', [FoundReportController::class, 'finderConversation']);
$router->post('/found/conversation/{token}/message', [FoundReportController::class, 'finderSendMessage']);
$router->get('/found/{token}/poll', [MessagePollController::class, 'foundMessages']);

// ── Authenticated routes ───────────────────────────────────────

$router->group('', [AuthMiddleware::class], function ($router) {
    // Dashboard
    $router->get('/dashboard', [BikeController::class, 'myBikes']);

    // Profile
    $router->get('/profile', [ProfileController::class, 'index']);
    $router->get('/profile/settings', [ProfileController::class, 'settings']);

    // Bike CRUD (BEFORE the public /bike/{hash} route!)
    $router->get('/bike/new', [BikeController::class, 'createForm']);
    $router->post('/bike/new', [BikeController::class, 'store']);
    $router->get('/bike/{id}/edit', [BikeController::class, 'editForm']);
    $router->post('/bike/{id}/edit', [BikeController::class, 'update']);
    $router->post('/bike/{id}/delete', [BikeController::class, 'delete']);

    // Photo management
    $router->post('/bike/{id}/photo/{photoId}/primary', [BikeController::class, 'setPrimaryPhoto']);
    $router->post('/bike/{id}/photo/{photoId}/delete', [BikeController::class, 'deletePhoto']);

    // Theft reporting (owner only)
    $router->get('/theft/report/{bikeId}', [TheftController::class, 'reportForm']);
    $router->post('/theft/report/{bikeId}', [TheftController::class, 'report']);
    $router->post('/theft/{reportId}/resolve', [TheftController::class, 'resolve']);

    // Found reports (owner — auth required)
    $router->get('/found/{reportId}/conversation', [FoundReportController::class, 'ownerConversation']);
    $router->post('/found/{reportId}/message', [FoundReportController::class, 'ownerSendMessage']);
    $router->post('/found/{reportId}/resolve', [FoundReportController::class, 'resolve']);
    $router->post('/found/{reportId}/close', [FoundReportController::class, 'closeConversation']);

    // Notifications (specific routes BEFORE parametric!)
    $router->get('/notifications', [NotificationController::class, 'index']);
    $router->get('/notifications/count', [NotificationController::class, 'unreadCount']);
    $router->post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    $router->post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Reservation system
    $router->get('/reservations', [ReservationController::class, 'myReservations']);
    $router->get('/reservation/new/{bikeId}', [ReservationController::class, 'createForm']);
    $router->post('/reservation/new/{bikeId}', [ReservationController::class, 'store']);
    $router->get('/reservation/{bikeId}/unavailable-dates', [ReservationController::class, 'unavailableDates']);
    $router->get('/reservation/{id}/poll', [MessagePollController::class, 'reservationMessages']);
    $router->get('/reservation/{id}', [ReservationController::class, 'detail']);
    $router->post('/reservation/{id}/approve', [ReservationController::class, 'approve']);
    $router->post('/reservation/{id}/reject', [ReservationController::class, 'reject']);
    $router->post('/reservation/{id}/cancel', [ReservationController::class, 'cancel']);
    $router->post('/reservation/{id}/activate', [ReservationController::class, 'activate']);
    $router->post('/reservation/{id}/complete', [ReservationController::class, 'complete']);
    $router->post('/reservation/{id}/not-returned', [ReservationController::class, 'reportNotReturned']);
    $router->post('/reservation/{id}/dispute', [ReservationController::class, 'dispute']);
    $router->post('/reservation/{id}/message', [ReservationController::class, 'sendMessage']);
    $router->post('/reservation/{id}/review', [ReservationController::class, 'submitReview']);
});

// ── Public bike detail (QR code scan) ──────────────────────────
// Must be AFTER /bike/new to avoid matching "new" as a hash
$router->get('/bike/{hash}', [BikeController::class, 'publicDetail']);

// ── Admin routes ───────────────────────────────────────────────

// $router->group('/admin', [AuthMiddleware::class], function ($router) {
//     $router->get('/dashboard', [AdminController::class, 'dashboard']);
// });