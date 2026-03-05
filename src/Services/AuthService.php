<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Entity\User;
use App\Repository\UserRepository;

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

    public function __construct(UserRepository $userRepository, Session $session)
    {
        $this->userRepository = $userRepository;
        $this->session = $session;
    }

    /**
     * Register a new user.
     * 
     * @return array{success: bool, user_id?: int, error?: string}
     */
    public function register(string $email, string $password, string $name, ?string $phone = null): array
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
            'name'               => $name,
            'phone'              => $phone,
            'verification_token' => $verificationToken,
        ]);

        // TODO: Send verification email

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Attempt to log in a user.
     * 
     * @return array{success: bool, user?: User, error?: string}
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return ['success' => false, 'error' => 'Neplatný e-mail nebo heslo.'];
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return ['success' => false, 'error' => 'Neplatný e-mail nebo heslo.'];
        }

        if ($user->isBanned()) {
            return ['success' => false, 'error' => 'Váš účet byl zablokován.'];
        }

        // Log in: store user ID in session
        $this->session->login($user->getId());

        // Update last login
        $this->userRepository->updateLastLogin($user->getId());

        // Rehash if bcrypt cost has changed
        if (password_needs_rehash($user->getPasswordHash(), PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->userRepository->updatePassword($user->getId(), $newHash);
        }

        return ['success' => true, 'user' => $user];
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