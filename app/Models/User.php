<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enum\TripStatus;
use App\Enum\UserStatus;
use Illuminate\Support\Str;
use App\Trait\UserRelationships;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        'next_of_kin_relationship',
        'verification_code',
        'verification_code_expires_at',
        'email_verified_at',
        'custom_fields',
        'avatar_url',
        'uuid',
        'phone_number',
        'email',
        'email_verified',
        'password',
        'transit_company_union_id',
        'profile_photo',
        'public_id',
        'driver_verified',
        'agent_id',
        'is_available',
        'lng',
        'lat',
        'trip_extended_time',
        'inbox_notifications',
        'email_notifications',
        'status',
        'reason',
        'security_question_id',
        'security_answer',
        'fcm_token',
        'is_premium_driver',
        'created_by',
        'referral_code',
        'state_id',
        'zone_id',
        'classification_id',
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
        'updated_at',
        'inbox_notifications',
        'email_notifications',
    ];

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
            'is_available' => 'boolean',
            'inbox_notifications' => 'boolean',
            'email_notifications' => 'boolean',
            'status' => UserStatus::class,
            'is_premium_driver' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($trip): void {
            $trip->uuid = Str::uuid();
        });
        static::bootDeletesUserRelationships();
    }

    public function totalTrips(): Attribute
    {
        return Attribute::get(fn () => $this->trips()
            ->whereStatus(TripStatus::COMPLETED)
            ->count()
        );
    }

    public function walletBalance(): Attribute
    {
        return Attribute::get(fn () => $this->walletAccount?->balance);
    }

    public function walletAmount(): Attribute
    {
        return Attribute::get(fn () => $this->wallet + $this->walletBalance);
    }

    public function earningBalance(): Attribute
    {
        return Attribute::get(fn () => $this->walletAccount?->earnings);
    }

    public function hasCompletedOnboarding(): bool
    {
        $fields = [
            'first_name',
            'last_name',
            'next_of_kin_full_name',
            'next_of_kin_phone_number',
            'next_of_kin_gender',
        ];

        return collect($fields)->every(fn($field) => !empty($this->$field));
    }

    public function getTotalBookingsAmount()
    {
        return $this->agentTripBookings()->sum('amount_paid');
    }

    public function checkAndUpgradeLevel()
    {
        // Get the agent's total booking amount (you can modify this based on your booking records)
        $totalBookings = $this->getTotalBookingsAmount();

        if ($totalBookings === 0) {
            // No bookings, no upgrade possible
            return;
        }

        // Get the current classification level
        $currentClassification = $this->classification;

        $highestLevel = AgentClassification::orderByRaw("FIELD(level, 'A', 'B', 'C', 'D')")
                            ->orderBy('amount', 'desc')
                            ->first();

        if ($currentClassification->level == $highestLevel->level) {
            // Agent is already at the highest level, so no upgrade is possible
            return;
        }

        // Check if agent exceeds the threshold for the current level
        if ($totalBookings > $currentClassification->amount) {
            $nextLevel = $this->getNextLevel($currentClassification);

            // Upgrade agent to the next level
            $this->classification()->associate($nextLevel);
            $this->walletAccount()->increment('balance', $nextLevel->amount); // Optionally, you can add the level amount to the wallet balance
            $this->save();
        }
    }

    public function getNextLevel($currentClassification)
    {
        // Find the next level (this could be more dynamic based on your business rules)
        return AgentClassification::where('level', '>', $currentClassification->level)
                                  ->orderBy('level', 'asc')
                                  ->first();
    }
}
