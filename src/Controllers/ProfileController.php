<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\BikeRepository;
use App\Repository\ReservationRepository;
use App\Repository\FoundReportRepository;
use App\Repository\UserPreferencesRepository;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\TwoFactorService;

class ProfileController
{
    public function __construct(
        private AuthService $authService,
        private BikeRepository $bikeRepo,
        private ReservationRepository $reservationRepo,
        private FoundReportRepository $foundReportRepo,
        private UserRepository $userRepo,
        private UserPreferencesRepository $preferencesRepo,
        private TwoFactorService $twoFactorService,
        private ActivityLogService $activityLog,
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

        $preferences = $this->preferencesRepo->findByUserId($user->getId());
        $recoveryCodesRemaining = $user->isTotpEnabled()
            ? $this->twoFactorService->countUnusedCodes($user->getId())
            : 0;

        return view('profile/settings', [
            'title'                  => 'Nastavení profilu – BikeSwap',
            'user'                   => $user,
            'preferences'            => $preferences,
            'twoFactorEnabled'       => $user->isTotpEnabled(),
            'recoveryCodesRemaining' => $recoveryCodesRemaining,
            'csrf'                   => $this->session->csrfToken(),
            'session'                => $this->session,
            'currentUser'            => $user,
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
        $validator
            ->required('first_name', 'Jméno je povinné.')
            ->required('surname', 'Příjmení je povinné.')
            ->phone('phone', 'Neplatný formát telefonu. Použijte formát +420 123 456 789.');

        if ($validator->fails()) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect('/profile/settings');
        }

        $address = trim($request->input('address', ''));
        if ($address !== '' && $request->input('address_validated', '0') !== '1') {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', 'Vyberte adresu z nabídky nebo použijte tlačítko "Zjistit moji polohu".');
            return redirect('/profile/settings');
        }

        $this->userRepo->updateProfile($user->getId(), [
            'first_name' => trim($request->input('first_name', '')),
            'surname'    => trim($request->input('surname', '')),
            'phone'      => preg_replace('/\s+/', '', trim($request->input('phone', ''))) ?: null,
            'address'    => trim($request->input('address', '')) ?: null,
        ]);

        $this->activityLog->log('profile_update', 'user', $user->getId());
        $this->session->flash('success', 'Profil byl úspěšně aktualizován.');
        return redirect('/profile/settings');
    }

    public function changePassword(Request $request): Response
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
        $validator
            ->required('current_password', 'Aktuální heslo je povinné.')
            ->required('new_password', 'Nové heslo je povinné.')
            ->minLength('new_password', 8, 'Nové heslo musí mít alespoň 8 znaků.')
            ->regex('new_password', '/[A-Z]/', 'Nové heslo musí obsahovat alespoň jedno velké písmeno.')
            ->regex('new_password', '/[0-9]/', 'Nové heslo musí obsahovat alespoň jedno číslo.')
            ->matches('new_password', 'new_password_confirmation', 'Nová hesla se neshodují.');

        if ($validator->fails()) {
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect('/profile/settings');
        }

        if (!password_verify($request->input('current_password', ''), $user->getPasswordHash())) {
            $this->session->flash('error', 'Aktuální heslo není správné.');
            return redirect('/profile/settings');
        }

        $hash = password_hash($request->input('new_password'), PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userRepo->updatePassword($user->getId(), $hash);

        $this->activityLog->log('password_change', 'user', $user->getId());
        $this->session->flash('success', 'Heslo bylo úspěšně změněno.');
        return redirect('/profile/settings');
    }

    public function changeEmail(Request $request): Response
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
        $validator
            ->required('new_email', 'Nový e-mail je povinný.')
            ->email('new_email', 'Neplatný formát e-mailu.')
            ->maxLength('new_email', 255, 'E-mail je příliš dlouhý.')
            ->required('email_current_password', 'Heslo je povinné pro změnu e-mailu.');

        if ($validator->fails()) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', $validator->allErrors()[0]);
            return redirect('/profile/settings#email');
        }

        if (!password_verify($request->input('email_current_password', ''), $user->getPasswordHash())) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', 'Aktuální heslo není správné.');
            return redirect('/profile/settings#email');
        }

        $newEmail = strtolower(trim($request->input('new_email', '')));

        if ($newEmail === strtolower($user->getEmail())) {
            $this->session->flash('error', 'Nový e-mail je stejný jako stávající.');
            return redirect('/profile/settings#email');
        }

        if ($this->userRepo->emailExists($newEmail)) {
            $this->session->setOldInput($request->all());
            $this->session->flash('error', 'Tento e-mail je již zaregistrován.');
            return redirect('/profile/settings#email');
        }

        $this->userRepo->updateEmail($user->getId(), $newEmail);

        $this->activityLog->log('email_change', 'user', $user->getId());
        $this->session->flash('success', 'E-mail byl úspěšně změněn na ' . $newEmail . '.');
        return redirect('/profile/settings');
    }

    public function updatePreferences(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login?redirect=/profile/settings');
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/profile/settings');
        }

        $this->preferencesRepo->save($user->getId(), [
            'email_on_found_report'  => $request->input('email_on_found_report') !== null,
            'email_on_reservation'   => $request->input('email_on_reservation') !== null,
            'email_on_message'       => $request->input('email_on_message') !== null,
            'email_on_status_change' => $request->input('email_on_status_change') !== null,
        ]);

        $this->session->flash('success', 'E-mailové předvolby byly uloženy.');
        return redirect('/profile/settings');
    }
}
