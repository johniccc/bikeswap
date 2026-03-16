<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Response\Response;
use App\Services\AuthService;

class HomeController
{
    private Session $session;
    private AuthService $authService;

    public function __construct(Session $session, AuthService $authService)
    {
        $this->session = $session;
        $this->authService = $authService;
    }

    public function index(Request $request): Response
    {
        if ($request->wantsJson()) {
            return json([
                'name' => 'BikeSwap',
                'status' => 'running',
            ]);
        }

        $currentUser = $this->session->isLoggedIn() ? $this->authService->currentUser() : null;

        return view('home/index', [
            'title' => 'BikeSwap – Ochrana vašeho kola',
            'session' => $this->session,
            'currentUser' => $currentUser,
        ])->withLayout('layouts/public');
    }

    public function privacy(Request $request): Response
    {
        $currentUser = $this->session->isLoggedIn() ? $this->authService->currentUser() : null;

        return view('privacy', [
            'title' => 'Ochrana osobních údajů – BikeSwap',
            'session' => $this->session,
            'currentUser' => $currentUser,
        ])->withLayout('layouts/public');
    }
}