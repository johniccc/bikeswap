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
use App\Controllers\BikeWarningController;
use App\Controllers\FileController;
use App\Controllers\TheftController;
use App\Controllers\FoundReportController;
use App\Controllers\MessagePollController;
use App\Controllers\NotificationController;
use App\Controllers\ReservationController;
use App\Controllers\ProfileController;

use App\Controllers\TwoFactorController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\AdminBikeController;
use App\Controllers\Admin\AdminReservationController;
use App\Controllers\Admin\AdminConversationController;
use App\Controllers\Admin\AdminBikeWarningController;
use App\Controllers\CookieConsentController;
use App\Controllers\DeviceFingerprintController;

use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\CsrfMiddleware;

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
$router->get('/login', [AuthController::class, 'loginForm']);
$router->get('/login/2fa', [TwoFactorController::class, 'verifyForm']);
$router->get('/login/2fa/cancel', [TwoFactorController::class, 'cancelVerify']);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->get('/forgot-password/methods', [AuthController::class, 'resetMethodsForm']);
$router->get('/forgot-password/totp', [AuthController::class, 'resetTotpForm']);
$router->get('/forgot-password/recovery', [AuthController::class, 'resetRecoveryForm']);
$router->get('/reset-password', [AuthController::class, 'resetPasswordForm']);

// ── Found reports (public — no auth required) ──────────────────

$router->get('/found/report/{qrHash}', [FoundReportController::class, 'reportForm']);
$router->get('/found/conversation/{token}', [FoundReportController::class, 'finderConversation']);
$router->get('/found/{token}/poll', [MessagePollController::class, 'foundMessages']);

// ── Public POST routes (CSRF protected) ──────────────────────────

$router->group('', [CsrfMiddleware::class], function ($router) {
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->post('/login/2fa', [TwoFactorController::class, 'verify']);
    $router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
    $router->post('/forgot-password/email', [AuthController::class, 'forgotPasswordEmail']);
    $router->post('/forgot-password/totp', [AuthController::class, 'forgotPasswordTotp']);
    $router->post('/forgot-password/recovery', [AuthController::class, 'forgotPasswordRecovery']);
    $router->post('/reset-password', [AuthController::class, 'resetPassword']);
    $router->post('/found/report/{qrHash}', [FoundReportController::class, 'report']);
    $router->post('/found/conversation/{token}/message', [FoundReportController::class, 'finderSendMessage']);
});

// ── Authenticated routes ───────────────────────────────────────

$router->group('', [AuthMiddleware::class, CsrfMiddleware::class], function ($router) {
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

    // Bike warning actions (owner)
    $router->post('/warning/{bikeId}/pickup', [BikeWarningController::class, 'confirmPickup']);
});

// ── Bike search by serial/frame number ───────────────────────────
$router->get('/bike/search', [BikeController::class, 'searchByFrameNumber']);

// ── Public bike detail (QR code scan) ──────────────────────────
// Must be AFTER /bike/new and /bike/search to avoid matching as a hash
$router->get('/bike/{hash}', [BikeController::class, 'publicDetail']);

// ── Admin routes ───────────────────────────────────────────────

$router->group('/admin', [AdminMiddleware::class, CsrfMiddleware::class], function ($router) {
    $router->get('', [AdminDashboardController::class, 'dashboard']);
    $router->get('/users', [AdminUserController::class, 'users']);
    $router->get('/users/{id}', [AdminUserController::class, 'userDetail']);
    $router->post('/users/{id}/edit', [AdminUserController::class, 'updateUser']);
    $router->post('/users/{id}/delete', [AdminUserController::class, 'deleteUser']);
    $router->post('/users/{id}/ban', [AdminUserController::class, 'banUser']);
    $router->post('/users/{id}/unban', [AdminUserController::class, 'unbanUser']);
    $router->post('/users/{id}/role', [AdminUserController::class, 'changeRole']);
    $router->get('/warnings', [AdminBikeWarningController::class, 'bikeWarnings']);
    $router->get('/warnings/new', [AdminBikeWarningController::class, 'createBikeWarningForm']);
    $router->post('/warnings/new', [AdminBikeWarningController::class, 'storeBikeWarning']);
    $router->post('/warnings/{id}/resolve', [AdminBikeWarningController::class, 'resolveBikeWarning']);
    $router->post('/warnings/{bikeId}/seize', [AdminBikeWarningController::class, 'seizeBike']);
    $router->post('/warnings/{bikeId}/return', [AdminBikeWarningController::class, 'returnBike']);
    $router->get('/bikes', [AdminBikeController::class, 'bikes']);
    $router->get('/bikes/new', [AdminBikeController::class, 'createBikeForm']);
    $router->post('/bikes/new', [AdminBikeController::class, 'createBike']);
    $router->get('/bikes/{id}', [AdminBikeController::class, 'bikeDetail']);
    $router->post('/bikes/{id}/edit', [AdminBikeController::class, 'updateBike']);
    $router->post('/bikes/{id}/delete', [AdminBikeController::class, 'deleteBike']);
    $router->get('/reservations', [AdminReservationController::class, 'reservations']);
    $router->get('/thefts', [AdminReservationController::class, 'thefts']);
    $router->get('/conversations', [AdminConversationController::class, 'conversations']);
    $router->get('/conversation/reservation/{id}', [AdminConversationController::class, 'reservationConversation']);
    $router->post('/conversation/reservation/{id}/message', [AdminConversationController::class, 'sendReservationMessage']);
    $router->get('/conversation/found/{id}', [AdminConversationController::class, 'foundConversation']);
    $router->post('/conversation/found/{id}/message', [AdminConversationController::class, 'sendFoundMessage']);
});