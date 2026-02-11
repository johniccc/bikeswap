<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Response\Response;
use App\Response\ViewResponse;
use App\Response\JsonResponse;

class HomeController
{
    public function index(Request $request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse([
                'name' => 'BikeSwap',
                'status' => 'running',
            ]);
        }

        return new ViewResponse('home/index', [
            'title' => 'BikeSwap – Ochrana vašeho kola',
        ]);
    }
}