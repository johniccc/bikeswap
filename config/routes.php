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

use App\Controllers\TwoFactorController;
use App\Controllers\AdminController;
use App\Controllers\CookieConsentController;
use App\Controllers\DeviceFingerprintController;

use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

// ── Public routes ──────────────────────────────────────────────

$router->get('/', [HomeController::class, 'index']);
$router->get('/stolen', [TheftController::class, 'publicList']);
$router->get('/shared', [ReservationController::class, 'sharedBikes']);
$router->get('/privacy', [HomeController::class, 'privacy']);

// ── API routes (no auth required) ────────────────────────────
$router->post('/api/cookie-consent', [CookieConsentController::class, 'store']);

// ── File serving (images, QR codes) ───────────────────────────

$router->get('/file/bike-photo/{id}', [FileController::class, 'bikePhoto']);
$router->get('/file/dispute-photo/{id}', [FileController::class, 'disputePhoto']);
$router->get('/file/qr/{hash}', [FileController::class, 'qrCode']);

// ── Auth routes ────────────────────────────────────────────────

$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/login/2fa', [TwoFactorController::class, 'verifyForm']);
$router->post('/login/2fa', [TwoFactorController::class, 'verify']);
$router->get('/login/2fa/cancel', [TwoFactorController::class, 'cancelVerify']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'resetPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// ── Found reports (public — no auth required) ──────────────────

$router->get('/found/report/{qrHash}', [FoundReportController::class, 'reportForm']);
$router->post('/found/report/{qrHash}', [FoundReportController::class, 'report']);
$router->get('/found/conversation/{token}', [FoundReportController::class, 'finderConversation']);
$router->post('/found/conversation/{token}/message', [FoundReportController::class, 'finderSendMessage']);
$router->get('/found/{token}/poll', [MessagePollController::class, 'foundMessages']);

// ── Authenticated routes ───────────────────────────────────────

$router->group('', [AuthMiddleware::class], function ($router) {
    // Device fingerprint API
    $router->post('/api/device-fingerprint', [DeviceFingerprintController::class, 'store']);

    // Dashboard
    $router->get('/dashboard', [BikeController::class, 'myBikes']);
    $router->get('/dashboard/poll', [BikeController::class, 'dashboardPoll']);

    // Profile
    $router->get('/profile', [ProfileController::class, 'index']);
    $router->get('/profile/settings', [ProfileController::class, 'settings']);
    $router->post('/profile/settings', [ProfileController::class, 'updateSettings']);
    $router->post('/profile/settings/email', [ProfileController::class, 'changeEmail']);
    $router->post('/profile/settings/preferences', [ProfileController::class, 'updatePreferences']);
    $router->post('/profile/settings/password', [ProfileController::class, 'changePassword']);

    // 2FA management
    $router->get('/profile/2fa/setup', [TwoFactorController::class, 'setupForm']);
    $router->post('/profile/2fa/enable', [TwoFactorController::class, 'enableSetup']);
    $router->get('/profile/2fa/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes']);
    $router->post('/profile/2fa/regenerate-codes', [TwoFactorController::class, 'regenerateCodes']);
    $router->get('/profile/2fa/disable', [TwoFactorController::class, 'disableForm']);
    $router->post('/profile/2fa/disable', [TwoFactorController::class, 'disable']);

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
    $router->get('/found/{reportId}/export-pdf', [FoundReportController::class, 'exportPdf']);

    // Notifications (specific routes BEFORE parametric!)
    $router->get('/notifications', [NotificationController::class, 'index']);
    $router->get('/notifications/count', [NotificationController::class, 'unreadCount']);
    $router->post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    $router->post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Reservation system
    $router->get('/reservations', [ReservationController::class, 'myReservations']);
    $router->get('/reservations/poll', [ReservationController::class, 'reservationsPoll']);
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
    $router->get('/reservation/{id}/not-returned', [ReservationController::class, 'notReturnedForm']);
    $router->post('/reservation/{id}/not-returned', [ReservationController::class, 'reportNotReturned']);
    $router->get('/reservation/{id}/dispute', [ReservationController::class, 'disputeForm']);
    $router->post('/reservation/{id}/dispute', [ReservationController::class, 'dispute']);
    $router->post('/reservation/{id}/admin-resolve', [ReservationController::class, 'adminResolve']);
    $router->post('/reservation/{id}/message', [ReservationController::class, 'sendMessage']);
    $router->post('/reservation/{id}/review', [ReservationController::class, 'submitReview']);
});

// ── Bike search by serial/frame number ───────────────────────────
$router->get('/bike/search', [BikeController::class, 'searchByFrameNumber']);

// ── Public bike detail (QR code scan) ──────────────────────────
// Must be AFTER /bike/new and /bike/search to avoid matching as a hash
$router->get('/bike/{hash}', [BikeController::class, 'publicDetail']);

// ── Admin routes ───────────────────────────────────────────────

$router->group('/admin', [AdminMiddleware::class], function ($router) {
    $router->get('', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
    $router->get('/users/{id}', [AdminController::class, 'userDetail']);
    $router->post('/users/{id}/edit', [AdminController::class, 'updateUser']);
    $router->post('/users/{id}/delete', [AdminController::class, 'deleteUser']);
    $router->post('/users/{id}/ban', [AdminController::class, 'banUser']);
    $router->post('/users/{id}/unban', [AdminController::class, 'unbanUser']);
    $router->post('/users/{id}/role', [AdminController::class, 'changeRole']);
    $router->get('/warnings', [AdminController::class, 'bikeWarnings']);
    $router->get('/warnings/new', [AdminController::class, 'createBikeWarningForm']);
    $router->post('/warnings/new', [AdminController::class, 'storeBikeWarning']);
    $router->post('/warnings/{id}/resolve', [AdminController::class, 'resolveBikeWarning']);
    $router->get('/bikes', [AdminController::class, 'bikes']);
    $router->get('/bikes/new', [AdminController::class, 'createBikeForm']);
    $router->post('/bikes/new', [AdminController::class, 'createBike']);
    $router->get('/bikes/{id}', [AdminController::class, 'bikeDetail']);
    $router->post('/bikes/{id}/edit', [AdminController::class, 'updateBike']);
    $router->post('/bikes/{id}/delete', [AdminController::class, 'deleteBike']);
    $router->get('/reservations', [AdminController::class, 'reservations']);
    $router->get('/thefts', [AdminController::class, 'thefts']);
    $router->get('/conversations', [AdminController::class, 'conversations']);
    $router->get('/conversation/reservation/{id}', [AdminController::class, 'reservationConversation']);
    $router->post('/conversation/reservation/{id}/message', [AdminController::class, 'sendReservationMessage']);
    $router->get('/conversation/found/{id}', [AdminController::class, 'foundConversation']);
    $router->post('/conversation/found/{id}/message', [AdminController::class, 'sendFoundMessage']);
});