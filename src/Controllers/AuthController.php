<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Response\Response;
use App\Response\ViewResponse;
use App\Response\JsonResponse;
use App\Response\RedirectResponse;
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
        return new ViewResponse('auth/register', [
            'title' => 'Registrace – BikeSwap',
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    /**
     * Process registration.
     */
    public function register(Request $request): Response
    {
        // CSRF check
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');

            return new RedirectResponse('/register');
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
                return new JsonResponse(['errors' => $validator->errors()], 422);
            }

            $this->session->flash('error', $validator->allErrors()[0]);

            return new RedirectResponse('/register');
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
                return new JsonResponse(['error' => $result['error']], 409);
            }

            $this->session->flash('error', $result['error']);

            return new RedirectResponse('/register');
        }

        if ($request->wantsJson()) {
            return new JsonResponse(['user_id' => $result['user_id']], 201);
        }

        $this->session->flash('success', 'Registrace proběhla úspěšně. Můžete se přihlásit.');

        return new RedirectResponse('/login');
    }

    /**
     * Show the login form.
     */
    public function loginForm(Request $request): Response
    {
        return new ViewResponse('auth/login', [
            'title' => 'Přihlášení – BikeSwap',
            'csrf' => $this->session->csrfToken(),
            'session' => $this->session,
        ]);
    }

    /**
     * Process login.
     */
    public function login(Request $request): Response
    {
        // CSRF check
        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token. Zkuste to znovu.');

            return new RedirectResponse('/login');
        }

        // Validate input
        $validator = new Validator($request->all());
        $validator
            ->required('email', 'E-mail je povinný.')
            ->required('password', 'Heslo je povinné.');

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return new JsonResponse(['errors' => $validator->errors()], 422);
            }

            $this->session->flash('error', $validator->allErrors()[0]);

            return new RedirectResponse('/login');
        }

        // Attempt login
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return new JsonResponse(['error' => $result['error']], 401);
            }

            $this->session->flash('error', $result['error']);

            return new RedirectResponse('/login');
        }

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => 'Přihlášení úspěšné.']);
        }

        $this->session->flash('success', 'Vítejte zpět!');

        return new RedirectResponse('/dashboard');
    }

    /**
     * Log out and redirect to homepage.
     */
    public function logout(Request $request): Response
    {
        $this->authService->logout();

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => 'Odhlášení úspěšné.']);
        }

        return new RedirectResponse('/');
    }
}