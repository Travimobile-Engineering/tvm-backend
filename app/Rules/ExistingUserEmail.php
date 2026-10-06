<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a user exists for the given email address.
 *
 * Email is encrypted at rest, so the standard "exists:users,email" rule can no
 * longer match it. The lookup is performed through the deterministic blind
 * index (with a plaintext fallback for legacy rows) instead. The failure
 * message mirrors the original "exists" rule so API responses are unchanged.
 */
class ExistingUserEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '' || ! User::byEmail($value)->exists()) {
            $fail('The selected :attribute is invalid.');
        }
    }
}
