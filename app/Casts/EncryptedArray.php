<?php

declare(strict_types=1);

namespace App\Casts;

use App\Services\DataProtection\DataProtector;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypts a JSON/array attribute at rest while keeping array access on the
 * model. The value is JSON encoded, then encrypted, and reversed on access.
 *
 * @implements CastsAttributes<array<array-key, mixed>|null, array<array-key, mixed>|null>
 */
class EncryptedArray implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<array-key, mixed>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decrypted = app(DataProtector::class)->decrypt((string) $value);

        if ($decrypted === null || $decrypted === '') {
            return null;
        }

        $decoded = json_decode($decrypted, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<array-key, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(DataProtector::class)->encrypt(
            json_encode($value, JSON_THROW_ON_ERROR)
        );
    }
}
