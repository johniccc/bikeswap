<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\BikeRepository;
use App\Repository\ReservationRepository;
use App\Repository\FoundReportRepository;
use App\Response\Response;
use App\Services\AuthService;

class ProfileController
{
    public function __construct(
        private AuthService $authService,
        private BikeRepository $bikeRepo,
        private ReservationRepository $reservationRepo,
        private FoundReportRepository $foundReportRepo,
        private Session $session,
    ) {}

    public function index(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login?redirect=/profile');
        }

        $bikes        = $this->bikeRepo->findByOwner($user->getId(), withPhotos: true);
        $reservations = $this->reservationRepo->findByBorrower($user->getId());
        $foundReports = $this->foundReportRepo->findByReporter($user->getId());

        return view('profile/index', [
            'title'        => 'Profil – BikeSwap',
            'user'         => $user,
            'bikes'        => $bikes,
            'reservations' => $reservations,
            'foundReports' => $foundReports,
            'session'      => $this->session,
            'currentUser'  => $user,
        ]);
    }

    public function settings(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login?redirect=/profile/settings');
        }

        return view('profile/settings', [
            'title'       => 'Nastavení profilu – BikeSwap',
            'user'        => $user,
            'session'     => $this->session,
            'currentUser' => $user,
        ]);
    }
}
