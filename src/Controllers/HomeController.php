<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Response\Response;

class HomeController
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function index(Request $request): Response
    {
        if ($request->wantsJson()) {
            return json([
                'name' => 'BikeSwap',
                'status' => 'running',
            ]);
        }

        return view('home/index', [
            'title' => 'BikeSwap – Ochrana vašeho kola',
            'session' => $this->session,
        ]);
    }
}