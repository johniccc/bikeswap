<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\ActivityLogService;

/**
 * Authentication service.
 * 
 * Handles registration, login, logout, and password verification.
 * This is the business logic layer — controllers call this, not the repository directly.
 */
class AuthService
{
    private UserRepository $userRepository;
    private Session $session;
    private ActivityLogService $activityLog;

    public function __construct(UserRepository $userRepository, Session $session, ActivityLogService $activityLog)
    {
        $this->userRepository = $userRepository;
        $this->session = $session;
        $this->activityLog = $activityLog;
    }

    /**
     * Register a new user.
     * 
     * @return array{success: bool, user_id?: int, error?: string}
     */
    public function register(string $email, string $password, string $firstName, string $surname, ?string $phone = null): array
    {
        // Check for duplicate email
        if ($this->userRepository->emailExists($email)) {
            return ['success' => false, 'error' => 'Uživatel s tímto e-mailem již existuje.'];
        }

        // Hash the password
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));

        // Create user
        $userId = $this->userRepository->create([
            'email'              => $email,
            'password_hash'      => $hash,
            'first_name'         => $firstName,
            'surname'            => $surname,
            'phone'              => $phone,
            'verification_token' => $verificationToken,
        ]);

        // TODO: Send verification email

        $this->activityLog->log('register', 'user', $userId);

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Attempt to log in a user.
     *
     * @return array{success: bool, user?: User, requires_2fa?: bool, error?: string}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return ['success' => false, 'error' => 'Neplatný e-mail nebo heslo.'];
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            $this->activityLog->log('login_failed', 'user', $user->getId());
            return ['success' => false, 'error' => 'Neplatný e-mail nebo heslo.'];
        }

        if ($user->isBanned()) {
            return ['success' => false, 'error' => 'Váš účet byl zablokován.'];
        }

        // Rehash if bcrypt cost has changed
        if (password_needs_rehash($user->getPasswordHash(), PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->userRepository->updatePassword($user->getId(), $newHash);
        }

        // If 2FA is enabled, enter half-auth state
        if ($user->isTotpEnabled()) {
            $this->session->set('2fa_user_id', $user->getId());
            $this->session->set('2fa_expires', time() + 300); // 5 min
            return ['success' => true, 'requires_2fa' => true, 'user' => $user];
        }

        // Full login
        $this->session->login($user->getId());
        $this->userRepository->updateLastLogin($user->getId());
        $this->activityLog->log('login', 'user', $user->getId());

        return ['success' => true, 'user' => $user];
    }

    /**
     * Complete login after successful 2FA verification.
     */
    public function complete2faLogin(int $userId): void
    {
        $this->session->login($userId);
        $this->session->remove('2fa_user_id');
        $this->session->remove('2fa_expires');
        $this->userRepository->updateLastLogin($userId);
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        $this->session->logout();
    }

    /**
     * Get the currently authenticated user, or null.
     */
    public function currentUser(): ?User
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    /**
     * Verify a user's email using their verification token.
     */
    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findByVerificationToken($token);

        if ($user === null) {
            return false;
        }

        $this->userRepository->verify($user->getId());

        return true;
    }
}