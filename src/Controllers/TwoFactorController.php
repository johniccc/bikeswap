<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Repository\UserRepository;
use App\Response\Response;
use App\Services\AuthService;
use App\Services\TwoFactorService;

class TwoFactorController
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private AuthService $authService,
        private UserRepository $userRepo,
        private Session $session,
    ) {}

    // ── Login 2FA ────────────────────────────────────────────────

    public function verifyForm(Request $request): Response
    {
        $userId = $this->session->get('2fa_user_id');
        $expires = $this->session->get('2fa_expires', 0);

        if (!$userId || time() > $expires) {
            $this->session->remove('2fa_user_id');
            $this->session->remove('2fa_expires');
            $this->session->flash('error', 'Platnost ověření vypršela. Přihlaste se znovu.');
            return redirect('/login');
        }

        return view('auth/two-factor', [
            'title'   => 'Dvoufaktorové ověření – BikeSwap',
            'csrf'    => $this->session->csrfToken(),
            'session' => $this->session,
        ])->withLayout('layouts/public');
    }

    public function verify(Request $request): Response
    {
        $userId = $this->session->get('2fa_user_id');
        $expires = $this->session->get('2fa_expires', 0);

        if (!$userId || time() > $expires) {
            $this->session->remove('2fa_user_id');
            $this->session->remove('2fa_expires');
            $this->session->flash('error', 'Platnost ověření vypršela. Přihlaste se znovu.');
            return redirect('/login');
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/login/2fa');
        }

        $code = trim($request->input('code', ''));
        $useRecovery = (bool) $request->input('use_recovery', false);

        if ($code === '') {
            $this->session->flash('error', 'Zadejte ověřovací kód.');
            return redirect('/login/2fa');
        }

        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            $this->session->remove('2fa_user_id');
            $this->session->remove('2fa_expires');
            return redirect('/login');
        }

        $verified = false;

        if ($useRecovery) {
            $verified = $this->twoFactorService->verifyRecoveryCode($userId, $code);
        } else {
            $secret = $user->getTotpSecret();
            if ($secret !== null) {
                $verified = $this->twoFactorService->verifyCode($secret, $code);
            }
        }

        if (!$verified) {
            $this->session->flash('error', $useRecovery
                ? 'Neplatný záložní kód.'
                : 'Neplatný ověřovací kód.');
            return redirect('/login/2fa');
        }

        // Complete login
        $this->authService->complete2faLogin($userId);

        // Warn about low recovery codes
        if ($useRecovery) {
            $remaining = $this->twoFactorService->countUnusedCodes($userId);
            if ($remaining <= 2) {
                $this->session->flash('warning', "Zbývají vám pouze $remaining záložní kódy. Doporučujeme vygenerovat nové v nastavení profilu.");
            }
        }

        $this->session->flash('success', 'Vítejte zpět!');

        $redirect = $this->session->get('auth_redirect', '');
        $this->session->remove('auth_redirect');

        if (!$redirect) {
            $redirect = ($user->isPolice()) ? '/admin' : '/dashboard';
        }

        return redirect($redirect);
    }

    public function cancelVerify(Request $request): Response
    {
        $this->session->remove('2fa_user_id');
        $this->session->remove('2fa_expires');
        return redirect('/login');
    }

    // ── Setup 2FA (authenticated) ────────────────────────────────

    public function setupForm(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login');
        }

        if ($user->isTotpEnabled()) {
            $this->session->flash('error', 'Dvoufaktorové ověření je již aktivní.');
            return redirect('/profile/settings');
        }

        // Generate or reuse secret from session
        $secret = $this->session->get('2fa_setup_secret');
        if (!$secret) {
            $secret = $this->twoFactorService->generateSecret();
            $this->session->set('2fa_setup_secret', $secret);
        }

        $provisioningUri = $this->twoFactorService->getProvisioningUri($user, $secret);
        $qrDataUri = $this->twoFactorService->generateQrDataUri($provisioningUri);

        return view('profile/two-factor-setup', [
            'title'     => 'Aktivace 2FA – BikeSwap',
            'user'      => $user,
            'secret'    => $secret,
            'qrDataUri' => $qrDataUri,
            'csrf'      => $this->session->csrfToken(),
            'session'   => $this->session,
            'currentUser' => $user,
        ])->withLayout('layouts/app');
    }

    public function enableSetup(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login');
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/profile/2fa/setup');
        }

        $secret = $this->session->get('2fa_setup_secret');
        if (!$secret) {
            $this->session->flash('error', 'Nastavení vypršelo. Zkuste to znovu.');
            return redirect('/profile/2fa/setup');
        }

        $code = trim($request->input('code', ''));
        if ($code === '' || !$this->twoFactorService->verifyCode($secret, $code)) {
            $this->session->flash('error', 'Neplatný ověřovací kód. Zkuste to znovu.');
            return redirect('/profile/2fa/setup');
        }

        // Generate recovery codes and enable
        $codes = $this->twoFactorService->generateRecoveryCodes();
        $this->twoFactorService->enable($user->getId(), $secret, $codes);

        // Store codes in session for one-time display
        $this->session->set('2fa_recovery_codes', $codes);
        $this->session->remove('2fa_setup_secret');

        $this->session->flash('success', 'Dvoufaktorové ověření bylo aktivováno.');
        return redirect('/profile/2fa/recovery-codes');
    }

    public function showRecoveryCodes(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login');
        }

        $codes = $this->session->get('2fa_recovery_codes');
        if (!$codes) {
            return redirect('/profile/settings');
        }

        // Remove from session — one-time view
        $this->session->remove('2fa_recovery_codes');

        return view('profile/two-factor-recovery', [
            'title'       => 'Záložní kódy – BikeSwap',
            'codes'       => $codes,
            'csrf'        => $this->session->csrfToken(),
            'session'     => $this->session,
            'currentUser' => $user,
        ])->withLayout('layouts/app');
    }

    public function regenerateCodes(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login');
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/profile/settings');
        }

        if (!$user->isTotpEnabled()) {
            return redirect('/profile/settings');
        }

        $codes = $this->twoFactorService->generateRecoveryCodes();

        // Delete old, store new (without re-writing the TOTP secret)
        $this->twoFactorService->storeRecoveryCodes($user->getId(), $codes);

        $this->session->set('2fa_recovery_codes', $codes);
        $this->session->flash('success', 'Záložní kódy byly obnoveny.');
        return redirect('/profile/2fa/recovery-codes');
    }

    // ── Disable 2FA ──────────────────────────────────────────────

    public function disableForm(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login');
        }

        if (!$user->isTotpEnabled()) {
            return redirect('/profile/settings');
        }

        return view('profile/two-factor-disable', [
            'title'       => 'Deaktivace 2FA – BikeSwap',
            'user'        => $user,
            'csrf'        => $this->session->csrfToken(),
            'session'     => $this->session,
            'currentUser' => $user,
        ])->withLayout('layouts/app');
    }

    public function disable(Request $request): Response
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return redirect('/login');
        }

        if (!$this->session->validateCsrf($request->input('_csrf', ''))) {
            $this->session->flash('error', 'Neplatný bezpečnostní token.');
            return redirect('/profile/2fa/disable');
        }

        if (!$user->isTotpEnabled()) {
            return redirect('/profile/settings');
        }

        // Verify password
        $password = $request->input('password', '');
        if (!password_verify($password, $user->getPasswordHash())) {
            $this->session->flash('error', 'Nesprávné heslo.');
            return redirect('/profile/2fa/disable');
        }

        // Verify TOTP code
        $code = trim($request->input('code', ''));
        $secret = $user->getTotpSecret();
        if ($code === '' || $secret === null || !$this->twoFactorService->verifyCode($secret, $code)) {
            $this->session->flash('error', 'Neplatný ověřovací kód.');
            return redirect('/profile/2fa/disable');
        }

        $this->twoFactorService->disable($user->getId());

        $this->session->flash('success', 'Dvoufaktorové ověření bylo deaktivováno.');
        return redirect('/profile/settings');
    }
}
