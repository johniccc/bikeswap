<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Repository\TwoFactorRepository;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\TwoFactorService;

class AuthController
{
    private AuthService $authService;
    private UserRepository $userRepo;
    private Session $session;
    private EmailService $emailService;
    private ActivityLogService $activityLog;
    private TwoFactorService $twoFactorService;
    private TwoFactorRepository $twoFactorRepo;
    private string $appUrl;

    public function __construct(AuthService $authService, UserRepository $userRepo, Session $session, EmailService $emailService, ActivityLogService $activityLog, TwoFactorService $twoFactorService, TwoFactorRepository $twoFactorRepo, array $config)
    {
        $this->authService      = $authService;
        $this->userRepo         = $userRepo;
        $this->session          = $session;
        $this->emailService     = $emailService;
        $this->activityLog      = $activityLog;
        $this->twoFactorService = $twoFactorService;
        $this->twoFactorRepo    = $twoFactorRepo;
        $this->appUrl           = rtrim($config['app']['url'] ?? 'http://localhost', '/');
    }

    /**
     * Show the registration form.
     */
    public function registerForm(Request $request): Response
    {
        $redirect = $request->query('redirect', '');
        if ($redirect && str_starts_with($redirect, '/')) {
            $this->session->set('auth_redirect', $redirect);
        }

        return view('auth/register', [
            'title' => 'Registrace – BikeSwap',
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Process registration.
     */
    public function register(Request $request): Response
    {
        // Validate input
        $validator = new Validator($request->all());
        $validator
            ->required('first_name', 'Jméno je povinné.')
            ->required('surname', 'Příjmení je povinné.')
            ->required('email', 'E-mail je povinný.')
            ->email('email')
            ->notDisposableEmail('email')
            ->phone('phone', 'Neplatný formát telefonu. Použijte formát +420 123 456 789.')
            ->password('password', 'password_confirmation');

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return json(['errors' => $validator->errors()], 422);
            }

            $this->session->setOldInput($request->all());
            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect('/register');
        }

        // Attempt registration
        $result = $this->authService->register(
            $request->input('email'),
            $request->input('password'),
            trim($request->input('first_name', '')),
            trim($request->input('surname', '')),
            preg_replace('/\s+/', '', trim($request->input('phone', ''))) ?: null
        );

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return json(['error' => $result['error']], 409);
            }

            $this->session->setOldInput($request->all());
            $this->session->flash('error', $result['error']);

            return redirect('/register');
        }

        if ($request->wantsJson()) {
            return json(['user_id' => $result['user_id']], 201);
        }

        $this->session->flash('registration_success', 'Registrace proběhla úspěšně! Na váš e-mail jsme odeslali ověřovací odkaz.');

        return redirect('/login');
    }

    /**
     * Show the login form.
     */
    public function loginForm(Request $request): Response
    {
        $redirect = $request->query('redirect', '');
        if ($redirect && str_starts_with($redirect, '/')) {
            $this->session->set('auth_redirect', $redirect);
        }

        return view('auth/login', [
            'title' => 'Přihlášení – BikeSwap',
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Process login.
     */
    public function login(Request $request): Response
    {
        // Validate input
        $validator = new Validator($request->all());
        $validator
            ->required('email', 'E-mail je povinný.')
            ->required('password', 'Heslo je povinné.');

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return json(['errors' => $validator->errors()], 422);
            }

            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect('/login');
        }

        // Attempt login
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return json(['error' => $result['error']], 401);
            }

            $this->session->flash('error', $result['error']);

            return redirect('/login');
        }

        // 2FA required — redirect to verification page
        if (!empty($result['requires_2fa'])) {
            if ($request->wantsJson()) {
                return json(['requires_2fa' => true]);
            }
            return redirect('/login/2fa');
        }

        if ($request->wantsJson()) {
            return json(['message' => 'Přihlášení úspěšné.']);
        }

        $user = $this->authService->currentUser();

        if ($user && !$user->isVerified()) {
            $this->session->flash('warning', 'Váš e-mail zatím nebyl ověřen.');
        }

        $this->session->flash('success', 'Vítejte zpět!');

        $redirect = $this->session->get('auth_redirect', '');
        $this->session->remove('auth_redirect');

        if (!$redirect) {
            $redirect = ($user && $user->isPolice()) ? '/admin' : '/dashboard';
        }

        return redirect($redirect);
    }

    /**
     * Log out and redirect to homepage.
     */
    public function logout(Request $request): Response
    {
        $this->activityLog->log('logout', 'user', $this->session->userId());
        $this->authService->logout();

        if ($request->wantsJson()) {
            return json(['message' => 'Odhlášení úspěšné.']);
        }

        return redirect('/');
    }

    /**
     * Verify email address via token from verification link.
     */
    public function verifyEmail(Request $request): Response
    {
        $token = $request->query('token', '');

        if ($token === '' || !$this->authService->verifyEmail($token)) {
            $this->session->flash('error', 'Ověřovací odkaz je neplatný nebo již byl použit.');
            return redirect('/');
        }

        $this->session->flash('success', 'E-mail byl úspěšně ověřen! Nyní se můžete přihlásit.');
        return redirect('/login');
    }

    /**
     * Show forgot password form.
     */
    public function forgotPasswordForm(Request $request): Response
    {
        return view('auth/forgot-password', [
            'title' => 'Zapomenuté heslo – BikeSwap',
            'csrf'  => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Process forgot password request — store email in session, redirect to method selection.
     */
    public function forgotPassword(Request $request): Response
    {
        $email = trim($request->input('email', ''));
        $user  = $this->userRepo->findByEmail($email);

        $expires = time() + 600; // 10 min

        if ($user !== null) {
            $has2fa     = (bool) $user->getTotpSecret();
            $hasRecovery = $has2fa && $this->twoFactorService->countUnusedCodes($user->getId()) > 0;

            $this->session->set('pw_reset_user_id', $user->getId());
            $this->session->set('pw_reset_email', $email);
            $this->session->set('pw_reset_has_2fa', $has2fa);
            $this->session->set('pw_reset_has_recovery', $hasRecovery);
        } else {
            // Anti-enumeration: store null user, only email option
            $this->session->set('pw_reset_user_id', null);
            $this->session->set('pw_reset_email', $email);
            $this->session->set('pw_reset_has_2fa', false);
            $this->session->set('pw_reset_has_recovery', false);
        }

        $this->session->set('pw_reset_step_expires', $expires);

        return redirect('/forgot-password/methods');
    }

    /**
     * Show method selection page (email / TOTP / recovery code).
     */
    public function resetMethodsForm(Request $request): Response
    {
        if (!$this->isResetStepValid()) {
            $this->session->flash('error', 'Platnost relace vypršela. Zadejte e-mail znovu.');
            return redirect('/forgot-password');
        }

        return view('auth/reset-methods', [
            'title'       => 'Způsob ověření – BikeSwap',
            'csrf'        => $this->session->csrfToken(),
            'email'       => $this->session->get('pw_reset_email'),
            'has2fa'      => $this->session->get('pw_reset_has_2fa', false),
            'hasRecovery' => $this->session->get('pw_reset_has_recovery', false),
            'session'     => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Send password reset email (extracted from old forgotPassword).
     */
    public function forgotPasswordEmail(Request $request): Response
    {
        if (!$this->isResetStepValid()) {
            $this->session->flash('error', 'Platnost relace vypršela. Zadejte e-mail znovu.');
            return redirect('/forgot-password');
        }

        $userId = $this->session->get('pw_reset_user_id');

        if ($userId !== null) {
            $user = $this->userRepo->findById($userId);
            if ($user !== null) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $this->userRepo->setPasswordResetToken($user->getId(), $token, $expires);

                $resetUrl = $this->appUrl . '/reset-password?token=' . $token;
                $this->emailService->sendPasswordReset($user->getEmail(), $resetUrl);
            }
        }

        $this->clearResetSession();

        $this->session->flash('success', 'Pokud účet s tímto e-mailem existuje, obdržíte instrukce pro obnovení hesla.');
        return redirect('/forgot-password');
    }

    /**
     * Show TOTP code form for password reset.
     */
    public function resetTotpForm(Request $request): Response
    {
        if (!$this->isResetStepValid() || !$this->session->get('pw_reset_has_2fa')) {
            $this->session->flash('error', 'Platnost relace vypršela. Zadejte e-mail znovu.');
            return redirect('/forgot-password');
        }

        return view('auth/reset-totp', [
            'title'   => 'TOTP ověření – BikeSwap',
            'csrf'    => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Verify TOTP code for password reset.
     */
    public function forgotPasswordTotp(Request $request): Response
    {
        if (!$this->isResetStepValid() || !$this->session->get('pw_reset_has_2fa')) {
            $this->session->flash('error', 'Platnost relace vypršela. Zadejte e-mail znovu.');
            return redirect('/forgot-password');
        }

        $code   = trim($request->input('code', ''));
        $userId = $this->session->get('pw_reset_user_id');

        if (!$userId) {
            $this->session->flash('error', 'Neplatná relace.');
            return redirect('/forgot-password');
        }

        $secret = $this->twoFactorRepo->getSecret($userId);

        if (!$secret || !$this->twoFactorService->verifyCode($secret, $code)) {
            $this->session->flash('error', 'Neplatný kód. Zkuste to znovu.');
            return redirect('/forgot-password/totp');
        }

        $this->setResetVerified($userId);
        $this->clearResetSession();

        return redirect('/reset-password');
    }

    /**
     * Show recovery code form for password reset.
     */
    public function resetRecoveryForm(Request $request): Response
    {
        if (!$this->isResetStepValid() || !$this->session->get('pw_reset_has_recovery')) {
            $this->session->flash('error', 'Platnost relace vypršela. Zadejte e-mail znovu.');
            return redirect('/forgot-password');
        }

        return view('auth/reset-recovery', [
            'title'   => 'Záložní kód – BikeSwap',
            'csrf'    => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Verify recovery code for password reset.
     */
    public function forgotPasswordRecovery(Request $request): Response
    {
        if (!$this->isResetStepValid() || !$this->session->get('pw_reset_has_recovery')) {
            $this->session->flash('error', 'Platnost relace vypršela. Zadejte e-mail znovu.');
            return redirect('/forgot-password');
        }

        $code   = trim($request->input('code', ''));
        $userId = $this->session->get('pw_reset_user_id');

        if (!$userId) {
            $this->session->flash('error', 'Neplatná relace.');
            return redirect('/forgot-password');
        }

        if (!$this->twoFactorService->verifyRecoveryCode($userId, $code)) {
            $this->session->flash('error', 'Neplatný záložní kód. Zkuste to znovu.');
            return redirect('/forgot-password/recovery');
        }

        $this->setResetVerified($userId);
        $this->clearResetSession();

        return redirect('/reset-password');
    }

    /**
     * Show reset password form.
     */
    public function resetPasswordForm(Request $request): Response
    {
        $token = $request->query('token', '');

        // Path 1: token-based (email link)
        if ($token) {
            $user = $this->userRepo->findByPasswordResetToken($token);
            if ($user === null) {
                $this->session->flash('error', 'Odkaz pro obnovení hesla je neplatný nebo vypršel.');
                return redirect('/forgot-password');
            }

            return view('auth/reset-password', [
                'title'   => 'Nové heslo – BikeSwap',
                'csrf'    => $this->session->csrfToken(),
                'token'   => $token,
                'session' => $this->session,
            ])->withLayout('layouts/public');
        }

        // Path 2: session-based (TOTP / recovery code)
        if (!$this->isResetVerified()) {
            $this->session->flash('error', 'Odkaz pro obnovení hesla je neplatný nebo vypršel.');
            return redirect('/forgot-password');
        }

        return view('auth/reset-password', [
            'title'   => 'Nové heslo – BikeSwap',
            'csrf'    => $this->session->csrfToken(),
            'token'   => '',
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Process reset password form.
     */
    public function resetPassword(Request $request): Response
    {
        $token = $request->input('token', '');
        $user  = null;

        if ($token) {
            // Path 1: token-based
            $user = $this->userRepo->findByPasswordResetToken($token);
        } else {
            // Path 2: session-based
            if ($this->isResetVerified()) {
                $userId = $this->session->get('pw_reset_verified');
                $user   = $this->userRepo->findById($userId);
            }
        }

        if ($user === null) {
            $this->session->flash('error', 'Odkaz pro obnovení hesla je neplatný nebo vypršel.');
            return redirect('/forgot-password');
        }

        $validator = new Validator($request->all());
        $validator
            ->password('password', 'password_confirmation');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            if ($token) {
                return redirect('/reset-password?token=' . urlencode($token));
            }
            return redirect('/reset-password');
        }

        $hash = password_hash($request->input('password'), PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($user->getId(), $hash);

        if ($token) {
            $this->userRepo->clearPasswordResetToken($user->getId());
        }

        $this->clearVerifiedSession();

        $this->session->flash('success', 'Heslo bylo úspěšně změněno. Nyní se můžete přihlásit.');
        return redirect('/login');
    }

    // ── Password reset session helpers ────────────────────────

    private function isResetStepValid(): bool
    {
        $expires = $this->session->get('pw_reset_step_expires');
        return $expires !== null && time() <= $expires;
    }

    private function isResetVerified(): bool
    {
        $expires = $this->session->get('pw_reset_verified_expires');
        return $expires !== null && time() <= $expires && $this->session->get('pw_reset_verified') !== null;
    }

    private function setResetVerified(int $userId): void
    {
        $this->session->set('pw_reset_verified', $userId);
        $this->session->set('pw_reset_verified_expires', time() + 600);
    }

    private function clearResetSession(): void
    {
        $this->session->remove('pw_reset_user_id');
        $this->session->remove('pw_reset_email');
        $this->session->remove('pw_reset_has_2fa');
        $this->session->remove('pw_reset_has_recovery');
        $this->session->remove('pw_reset_step_expires');
    }

    private function clearVerifiedSession(): void
    {
        $this->session->remove('pw_reset_verified');
        $this->session->remove('pw_reset_verified_expires');
    }
}