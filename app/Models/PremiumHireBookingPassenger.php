<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Model;

class PremiumHireBookingPassenger extends Model
{
    protected $fillable = [
        'user_id',
        'premium_hire_booking_id',
        'name',
        'email',
        'phone_number',
        'gender',
        'next_of_kin',
        'next_of_kin_phone_number',
    ];

    protected function casts(): array
    {
        return [
            'email' => EncryptedAttribute::class,
            'phone_number' => EncryptedAttribute::class,
            'next_of_kin' => EncryptedAttribute::class,
            'next_of_kin_phone_number' => EncryptedAttribute::class,
        ];
    }

    public function premiumHireBooking()
    {
        return $this->belongsTo(PremiumHireBooking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
