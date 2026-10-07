<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Casts\EncryptedAttribute;
use App\Enum\General;
use App\Enum\TripStatus;
use App\Enum\UserStatus;
use App\Services\DataProtection\DataProtector;
use App\Trait\UserRelationships;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
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
        'email_hash',
        'phone_number_hash',
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
            'email' => EncryptedAttribute::class,
            'phone_number' => EncryptedAttribute::class,
            'address' => EncryptedAttribute::class,
            'next_of_kin_full_name' => EncryptedAttribute::class,
            'next_of_kin_phone_number' => EncryptedAttribute::class,
            'lng' => EncryptedAttribute::class,
            'lat' => EncryptedAttribute::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($trip): void {
            $trip->uuid = Str::uuid();
        });

        // Keep the login blind indexes in sync with their encrypted values.
        static::saving(function (self $user): void {
            $protector = app(DataProtector::class);
            $attributes = $user->getAttributes();

            if (array_key_exists('email', $attributes)) {
                $email = $user->getAttribute('email');
                $user->email_hash = filled($email) ? $protector->blindIndex((string) $email) : null;
            }

            if (array_key_exists('phone_number', $attributes)) {
                $phone = $user->getAttribute('phone_number');
                $user->phone_number_hash = filled($phone) ? $protector->blindIndex((string) $phone) : null;
            }
        });

        static::bootDeletesUserRelationships();
    }

    /**
     * Find users by email using the deterministic blind index, falling back to
     * any legacy plaintext rows that have not been encrypted yet.
     *
     * @return Builder<static>
     */
    public static function byEmail(string $email): Builder
    {
        $hash = app(DataProtector::class)->blindIndex($email);

        return static::query()->where(function (Builder $query) use ($hash, $email): void {
            $query->where('email_hash', $hash)->orWhere('email', $email);
        });
    }

    /**
     * Find users by phone number using the deterministic blind index, falling
     * back to any legacy plaintext rows that have not been encrypted yet.
     *
     * @return Builder<static>
     */
    public static function byPhone(string $phone): Builder
    {
        $hash = app(DataProtector::class)->blindIndex($phone);

        return static::query()->where(function (Builder $query) use ($hash, $phone): void {
            $query->where('phone_number_hash', $hash)->orWhere('phone_number', $phone);
        });
    }

    // Attributes
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

    public function pendingBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->driverTripPayments()
                ->where('status', General::PENDING)
                ->sum('amount'),
        );
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

        return collect($fields)->every(fn ($field) => ! empty($this->$field));
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

            if ($nextLevel) {
                // Upgrade agent to the next level
                $this->classification()->associate($nextLevel);
                $this->walletAccount()->increment('balance', $nextLevel->reward_amount); // Add the level reward amount to the wallet balance
                $this->save();
            }
        }
    }

    public function getNextLevel($currentClassification)
    {
        // Find the next level (this could be more dynamic based on your business rules)
        return AgentClassification::where('level', '>', $currentClassification->level)
            ->orderBy('level', 'asc')
            ->first();
    }

    public function createEarning($title, $amount, $type, $status, $description = null)
    {
        $this->earnings()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'status' => $status,
        ]);
    }

    public function createTransaction($title, $amount, $type, $reference, ?int $receiverId = null, ?string $description = null)
    {
        $this->transactions()->create([
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'receiver_id' => $receiverId,
            'txn_reference' => $reference,
            'description' => $description,
        ]);
    }
}
