<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Trait\UserRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, UserRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'sms_verified',
        'user_category',
        'wallet',
        'txn_pin',
        'address',
        'gender',
        'is_admin',
        'nin',
        'next_of_kin_full_name',
        'next_of_kin_phone_number',
        'next_of_kin_gender',
        'verification_code',
        'verification_code_expires_at',
        'custom_fields',
        'avatar_url',
        'uuid',
        'phone_number',
        'email',
        'password',
        'transit_company_union_id',
        'profile_photo',
        'public_id',
        'driver_verified',
    ];

    protected $guarded = [
        'remember_token',
        'email_verified',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'verification_code_expires_at',
        'email_verified',
        'sms_verified',
        'email_verified_at',
        'is_admin',
        'created_at',
        'updated_at'
    ];

    // The JWT Identifier method required by the JWT package
    public function getJWTIdentifier(){
        return $this->getKey();
    }
// The JWT Custom Claims method required by the JWT package
    public function getJWTCustomClaims(){
        return [];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'driver_verified' => 'boolean',
        ];
    }
}
