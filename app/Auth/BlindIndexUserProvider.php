<?php

namespace App\Auth;

use App\Services\DataProtection\DataProtector;
use Closure;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Eloquent user provider that is aware of encrypted email and phone columns.
 * Credentials used for login are matched through their deterministic blind
 * index columns, with a plaintext fallback for legacy rows that have not been
 * backfilled yet.
 *
 * This keeps Auth::attempt() (e.g. the JWT "agent" guard) and the password
 * broker working while the underlying columns are encrypted at rest.
 */
class BlindIndexUserProvider extends EloquentUserProvider
{
    /**
     * Map of credential key => blind index column shared by the supported
     * user tables (users.phone_number_hash, agents.phone_hash).
     *
     * @var array<string, string>
     */
    private const HASH_COLUMNS = [
        'email' => 'email_hash',
        'phone_number' => 'phone_number_hash',
        'phone' => 'phone_hash',
    ];

    /**
     * @param  array<string, mixed>  $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains((string) $key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if ($credentials === []) {
            return null;
        }

        $protector = app(DataProtector::class);
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);

                continue;
            }

            if ($value instanceof Closure) {
                $value($query);

                continue;
            }

            if (isset(self::HASH_COLUMNS[$key]) && is_string($value)) {
                $hashColumn = self::HASH_COLUMNS[$key];

                $query->where(function ($query) use ($value, $protector, $hashColumn, $key): void {
                    $query->where($hashColumn, $protector->blindIndex($value))
                        ->orWhere($key, $value);
                });

                continue;
            }

            $query->where($key, $value);
        }

        return $query->first();
    }
}
