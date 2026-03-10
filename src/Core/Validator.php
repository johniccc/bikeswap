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

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}