<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\ReservationRepository;
use App\Repository\FoundReportRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;

class ProfileController
{
    public function __construct(
        private AuthService $authService,
        private BikeRepository $bikeRepo,
        private ReservationRepository $reservationRepo,
        private FoundReportRepository $foundReportRepo,
        private UserRepository $userRepo,
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
        ])->withLayout('layouts/app');
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
            'csrf'        => $this->session->csrfToken(),
            'session'     => $this->session,
            'currentUser' => $user,
        ])->withLayout('layouts/app');
    }

    public function updateSettings(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login?redirect=/profile/settings');
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/profile/settings');
        }

        $validator = new Validator($request->all());
        $validator->required('name', 'Jméno je povinné.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect('/profile/settings');
        }

        $this->userRepo->updateProfile($user->getId(), [
            'name'    => trim($request->input('name', '')),
            'phone'   => trim($request->input('phone', '')) ?: null,
            'address' => trim($request->input('address', '')) ?: null,
        ]);

        $this->session->flash('success', 'Profil byl úspěšně aktualizován.');
        return redirect('/profile/settings');
    }
}
