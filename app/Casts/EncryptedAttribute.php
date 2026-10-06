<?php

declare(strict_types=1);

namespace App\Casts;

use App\Services\DataProtection\DataProtector;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Transparently encrypts a model attribute at rest and decrypts it on access.
 *
 * Register on a model via the casts() method:
 *     'address' => EncryptedAttribute::class,
 *
 * Reads are backwards compatible: plaintext values stored before encryption
 * was introduced are returned untouched.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedAttribute implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(DataProtector::class)->decrypt((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return app(DataProtector::class)->encrypt((string) $value);
    }
}
