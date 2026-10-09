<?php

declare(strict_types=1);

namespace App\Casts;

use App\Services\DataProtection\DataProtector;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Transparently encrypts a JSON attribute at rest and decodes it on access.
 *
 * Register on a model via the casts() method:
 *     'response' => EncryptedJson::class,
 *
 * The value is JSON encoded before encryption (the encrypted payload is not
 * valid JSON, so the underlying column must be widened to TEXT). Reads are
 * backwards compatible: plaintext JSON stored before encryption was introduced
 * is decoded as before.
 *
 * @implements CastsAttributes<mixed, mixed>
 */
class EncryptedJson implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $decrypted = app(DataProtector::class)->decrypt((string) $value);

        if ($decrypted === null) {
            return null;
        }

        $decoded = json_decode($decrypted, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $decrypted;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return app(DataProtector::class)->encrypt(json_encode($value));
    }
}
