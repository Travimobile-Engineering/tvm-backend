<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures the phone number is not already used by another user.
 *
 * Phone number is encrypted at rest, so the standard "unique:users,phone_number"
 * rule can no longer match it. Uniqueness is checked through the deterministic
 * blind index (with a plaintext fallback for legacy rows). The failure message
 * mirrors the original "unique" rule so API responses are unchanged.
 */
class UniqueUserPhone implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute has already been taken.');

            return;
        }

        $normalized = function_exists('formatPhoneNumber') ? formatPhoneNumber($value) : $value;

        $query = User::byPhone((string) $normalized);

        if ($this->ignoreUserId !== null) {
            $query->whereKeyNot($this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}
