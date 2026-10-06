<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Services\DataProtection\DataProtector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Agent extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'agents';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'state_of_origin',
        'residential_address',
        'company',
        'terms',
        'password',
        'platform',
        'start_date',
        'end_date',
        'is_default_password',
    ];

    protected $hidden = [
        'password',
        'email_hash',
        'phone_hash',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_default_password' => 'boolean',
            'email' => EncryptedAttribute::class,
            'phone' => EncryptedAttribute::class,
            'residential_address' => EncryptedAttribute::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $agent): void {
            $protector = app(DataProtector::class);
            $attributes = $agent->getAttributes();

            if (array_key_exists('email', $attributes)) {
                $email = $agent->getAttribute('email');
                $agent->email_hash = filled($email) ? $protector->blindIndex((string) $email) : null;
            }

            if (array_key_exists('phone', $attributes)) {
                $phone = $agent->getAttribute('phone');
                $agent->phone_hash = filled($phone) ? $protector->blindIndex((string) $phone) : null;
            }
        });
    }

    /**
     * Find agents by email using the deterministic blind index, with a legacy
     * plaintext fallback for rows that have not been backfilled yet.
     *
     * @return Builder<Agent>
     */
    public static function byEmail(string $email): Builder
    {
        $hash = app(DataProtector::class)->blindIndex($email);

        return static::query()->where(function (Builder $query) use ($hash, $email): void {
            $query->where('email_hash', $hash)->orWhere('email', $email);
        });
    }

    /**
     * Find agents by phone using the deterministic blind index, with a legacy
     * plaintext fallback for rows that have not been backfilled yet.
     *
     * @return Builder<Agent>
     */
    public static function byPhone(string $phone): Builder
    {
        $hash = app(DataProtector::class)->blindIndex($phone);

        return static::query()->where(function (Builder $query) use ($hash, $phone): void {
            $query->where('phone_hash', $hash)->orWhere('phone', $phone);
        });
    }

    // The JWT Identifier method required by the JWT package
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // The JWT Custom Claims method required by the JWT package
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function states(): BelongsToMany
    {
        return $this->belongsToMany(State::class, 'agent_state');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
