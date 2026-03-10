<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\EmailService;

class AuthController
{
    private AuthService $authService;
    private UserRepository $userRepo;
    private Session $session;
    private EmailService $emailService;
    private string $appUrl;

    public function __construct(AuthService $authService, UserRepository $userRepo, Session $session, EmailService $emailService, array $config)
    {
        $this->authService  = $authService;
        $this->userRepo     = $userRepo;
        $this->session      = $session;
        $this->emailService = $emailService;
        $this->appUrl       = rtrim($config['app']['url'] ?? 'http://localhost', '/');
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
        // CSRF check
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');

            return redirect('/register');
        }

        // Validate input
        $validator = new Validator($request->all());
        $validator
            ->required('name', 'Jméno je povinné.')
            ->required('email', 'E-mail je povinný.')
            ->email('email')
            ->required('password', 'Heslo je povinné.')
            ->minLength('password', 8, 'Heslo musí mít alespoň 8 znaků.')
            ->regex('password', '/[A-Z]/', 'Heslo musí obsahovat alespoň jedno velké písmeno.')
            ->regex('password', '/[0-9]/', 'Heslo musí obsahovat alespoň jedno číslo.')
            ->matches('password', 'password_confirmation', 'Hesla se neshodují.');

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return json(['errors' => $validator->errors()], 422);
            }

            $this->session->flash('error', $validator->allErrors()[0]);

            return redirect('/register');
        }

        // Attempt registration
        $result = $this->authService->register(
            $request->input('email'),
            $request->input('password'),
            $request->input('name'),
            $request->input('phone')
        );

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return json(['error' => $result['error']], 409);
            }

            $this->session->flash('error', $result['error']);

            return redirect('/register');
        }

        if ($request->wantsJson()) {
            return json(['user_id' => $result['user_id']], 201);
        }

        $this->session->flash('registration_success', 'Registrace proběhla úspěšně! Nyní se můžete přihlásit.');

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
        // CSRF check
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');

            return redirect('/login');
        }

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

        if ($request->wantsJson()) {
            return json(['message' => 'Přihlášení úspěšné.']);
        }

        $this->session->flash('success', 'Vítejte zpět!');

        $redirect = $this->session->get('auth_redirect', '');
        $this->session->remove('auth_redirect');

        if (!$redirect) {
            $user = $this->authService->currentUser();
            $redirect = ($user && $user->isPolice()) ? '/admin' : '/dashboard';
        }

        return redirect($redirect);
    }

    /**
     * Log out and redirect to homepage.
     */
    public function logout(Request $request): Response
    {
        $this->authService->logout();

        if ($request->wantsJson()) {
            return json(['message' => 'Odhlášení úspěšné.']);
        }

        return redirect('/');
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
     * Process forgot password request.
     */
    public function forgotPassword(Request $request): Response
    {
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/forgot-password');
        }

        $email = trim($request->input('email', ''));
        $user  = $this->userRepo->findByEmail($email);

        // Always show success to prevent user enumeration
        if ($user !== null) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            $this->userRepo->setPasswordResetToken($user->getId(), $token, $expires);

            $resetUrl = $this->appUrl . '/reset-password?token=' . $token;

            $this->emailService->sendPasswordReset($user->getEmail(), $resetUrl);
        }

        $this->session->flash('success', 'Pokud účet s tímto e-mailem existuje, obdržíte instrukce pro obnovení hesla.');
        return redirect('/forgot-password');
    }

    /**
     * Show reset password form.
     */
    public function resetPasswordForm(Request $request): Response
    {
        $token = $request->query('token', '');
        $user  = $token ? $this->userRepo->findByPasswordResetToken($token) : null;

        if ($user === null) {
            $this->session->flash('error', 'Odkaz pro obnovení hesla je neplatný nebo vypršel.');
            return redirect('/forgot-password');
        }

        return view('auth/reset-password', [
            'title' => 'Nové heslo – BikeSwap',
            'csrf'  => $this->session->csrfToken(),
            'token' => $token,
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    /**
     * Process reset password form.
     */
    public function resetPassword(Request $request): Response
    {
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/forgot-password');
        }

        $token = $request->input('token', '');
        $user  = $token ? $this->userRepo->findByPasswordResetToken($token) : null;

        if ($user === null) {
            $this->session->flash('error', 'Odkaz pro obnovení hesla je neplatný nebo vypršel.');
            return redirect('/forgot-password');
        }

        $validator = new Validator($request->all());
        $validator
            ->required('password', 'Heslo je povinné.')
            ->minLength('password', 8, 'Heslo musí mít alespoň 8 znaků.')
            ->regex('password', '/[A-Z]/', 'Heslo musí obsahovat alespoň jedno velké písmeno.')
            ->regex('password', '/[0-9]/', 'Heslo musí obsahovat alespoň jedno číslo.')
            ->matches('password', 'password_confirmation', 'Hesla se neshodují.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect('/reset-password?token=' . urlencode($token));
        }

        $hash = password_hash($request->input('password'), PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($user->getId(), $hash);
        $this->userRepo->clearPasswordResetToken($user->getId());

        $this->session->flash('success', 'Heslo bylo úspěšně změněno. Nyní se můžete přihlásit.');
        return redirect('/login');
    }
}