<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * UserPreferences entity.
 * 
 * Stores per-user email notification preferences.
 * All preferences default to TRUE (opt-out model).
 * 
 * Primary key is user_id (1:1 relationship with users table).
 */
class UserPreferences
{
    private int $userId;
    private bool $emailOnFoundReport;
    private bool $emailOnMessage;
    private bool $emailOnStatusChange;
    private bool $emailOnReservation;
    private string $updatedAt;

    private function __construct() {}

    public static function fromRow(array $row): self
    {
        $prefs = new self();

        $prefs->userId              = (int) $row['user_id'];
        $prefs->emailOnFoundReport  = (bool) ($row['email_on_found_report'] ?? true);
        $prefs->emailOnMessage      = (bool) ($row['email_on_message'] ?? true);
        $prefs->emailOnStatusChange = (bool) ($row['email_on_status_change'] ?? true);
        $prefs->emailOnReservation  = (bool) ($row['email_on_reservation'] ?? true);
        $prefs->updatedAt           = $row['updated_at'] ?? '';

        return $prefs;
    }

    /**
     * Create a default preferences object for a user (all enabled).
     * Used when the user has no row in user_preferences yet.
     */
    public static function defaults(int $userId): self
    {
        $prefs = new self();

        $prefs->userId              = $userId;
        $prefs->emailOnFoundReport  = true;
        $prefs->emailOnMessage      = true;
        $prefs->emailOnStatusChange = true;
        $prefs->emailOnReservation  = true;
        $prefs->updatedAt           = '';

        return $prefs;
    }

    // ── Getters ────────────────────────────────────────────────

    public function getUserId(): int { return $this->userId; }
    public function isEmailOnFoundReport(): bool { return $this->emailOnFoundReport; }
    public function isEmailOnMessage(): bool { return $this->emailOnMessage; }
    public function isEmailOnStatusChange(): bool { return $this->emailOnStatusChange; }
    public function isEmailOnReservation(): bool { return $this->emailOnReservation; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    /**
     * Convert to array for saving to database.
     */
    public function toArray(): array
    {
        return [
            'email_on_found_report'  => $this->emailOnFoundReport ? 1 : 0,
            'email_on_message'       => $this->emailOnMessage ? 1 : 0,
            'email_on_status_change' => $this->emailOnStatusChange ? 1 : 0,
            'email_on_reservation'   => $this->emailOnReservation ? 1 : 0,
        ];
    }
}