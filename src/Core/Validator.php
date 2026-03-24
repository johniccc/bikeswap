<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Input Validator.
 * 
 * Validates request data and collects errors.
 * 
 * Usage:
 *   $validator = new Validator($request->all());
 *   $validator->required('email')->email('email')->minLength('password', 8);
 *   if ($validator->fails()) { ... $validator->errors() ... }
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Field must be present and not empty.
     */
    public function required(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->addError($field, $message ?: "Pole '{$field}' je povinné.");
        }

        return $this;
    }

    /**
     * Field must be a valid email address.
     */
    public function email(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?: "Neplatná e-mailová adresa.");
        }

        return $this;
    }

    /**
     * Field must have a minimum length.
     */
    public function minLength(string $field, int $min, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && mb_strlen($value) < $min) {
            $this->addError($field, $message ?: "Pole '{$field}' musí mít alespoň {$min} znaků.");
        }

        return $this;
    }

    /**
     * Field must have a maximum length.
     */
    public function maxLength(string $field, int $max, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && mb_strlen($value) > $max) {
            $this->addError($field, $message ?: "Pole '{$field}' může mít maximálně {$max} znaků.");
        }

        return $this;
    }

    /**
     * Two fields must have the same value (e.g. password confirmation).
     */
    public function matches(string $field, string $otherField, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        $otherValue = $this->data[$otherField] ?? '';

        if ($value !== $otherValue) {
            $this->addError($field, $message ?: "Pole '{$field}' se neshoduje s polem '{$otherField}'.");
        }

        return $this;
    }

    /**
     * Field must be a valid integer.
     */
    public function integer(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $message ?: "Pole '{$field}' musí být celé číslo.");
        }

        return $this;
    }

    /**
     * Field value must be one of the allowed values.
     */
    public function in(string $field, array $allowed, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, $message ?: "Neplatná hodnota pole '{$field}'.");
        }

        return $this;
    }

    /**
     * Check if validation failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors.
     * Returns ['field' => ['error1', 'error2'], ...]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error for a field.
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get all errors as a flat list of messages.
     */
    public function allErrors(): array
    {
        $flat = [];

        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flat[] = $error;
            }
        }

        return $flat;
    }

    /**
     * Field must be a valid Czech phone number (+420 / 00420 + 9 digits).
     * Only validates when the field is non-empty (phone is optional).
     */
    public function phone(string $field, string $message = ''): self
    {
        $raw = $this->data[$field] ?? '';
        // Strip spaces before validating so both "+420 123 456 789" and "+420123456789" are accepted
        $value = preg_replace('/\s+/', '', $raw);

        if ($value !== '' && !preg_match('/^(\+420|00420)\d{9}$/', $value)) {
            $this->addError($field, $message ?: 'Neplatný formát telefonu. Použijte formát +420 123 456 789.');
        }

        return $this;
    }

    /**
     * Field value must match a regex pattern.
     */
    public function regex(string $field, string $pattern, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, $message ?: "Pole '{$field}' má neplatný formát.");
        }

        return $this;
    }

    /**
     * Email must not use a disposable/temporary email provider.
     */
    public function notDisposableEmail(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';

        if ($value === '' || !str_contains($value, '@')) {
            return $this;
        }

        $domain = strtolower(substr($value, strrpos($value, '@') + 1));
        $listPath = dirname(__DIR__, 2) . '/data/disposable_email_domains.txt';

        if (!is_file($listPath)) {
            return $this;
        }

        static $domains = null;
        if ($domains === null) {
            $domains = array_flip(array_filter(array_map('trim', file($listPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))));
        }

        if (isset($domains[$domain])) {
            $this->addError($field, $message ?: 'Dočasné e-mailové adresy nejsou povoleny.');
        }

        return $this;
    }

    /**
     * Validate a password field with standard rules (min 8 chars, uppercase, digit, confirmation).
     */
    public function password(string $field, string $confirmField): self
    {
        $this->required($field, 'Heslo je povinné.')
             ->minLength($field, 8, 'Heslo musí mít alespoň 8 znaků.')
             ->regex($field, '/[A-Z]/', 'Heslo musí obsahovat alespoň jedno velké písmeno.')
             ->regex($field, '/[0-9]/', 'Heslo musí obsahovat alespoň jedno číslo.')
             ->matches($field, $confirmField, 'Hesla se neshodují.');

        return $this;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}