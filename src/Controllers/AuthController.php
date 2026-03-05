<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Response\Response;
use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;
    private Session $session;

    public function __construct(AuthService $authService, Session $session)
    {
        $this->authService = $authService;
        $this->session = $session;
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
}