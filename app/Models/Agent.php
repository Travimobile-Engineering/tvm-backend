<?php

namespace App\Models;

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

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_default_password' => 'boolean',
        ];
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
