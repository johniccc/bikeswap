<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\BikeRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\BikeWarningService;

/**
 * Handles owner-side bike warning actions such as confirming pickup.
 */
class BikeWarningController
{
    private BikeWarningService $bikeWarningService;
    private BikeRepository $bikeRepository;
    private AuthService $authService;
    private Session $session;

    public function __construct(
        BikeWarningService $bikeWarningService,
        BikeRepository $bikeRepository,
        AuthService $authService,
        Session $session
    ) {
        $this->bikeWarningService = $bikeWarningService;
        $this->bikeRepository = $bikeRepository;
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Owner confirms bike pickup.
     * POST /warning/{bikeId}/pickup
     */
    public function confirmPickup(Request $request): Response
    {
        $currentUser = $this->authService->currentUser();
        $bikeId = (int) $request->param('bikeId');

        $bike = $this->bikeRepository->findById($bikeId);
        if ($bike === null) {
            throw new \RuntimeException('Kolo nenalezeno.', 404);
        }

        try {
            $this->bikeWarningService->confirmPickup($bikeId, $currentUser->getId());
            $this->session->flash('success', 'Vyzvednutí kola bylo potvrzeno.');
        } catch (\RuntimeException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        return redirect('/bike/' . $bike->getQrHash());
    }
}
