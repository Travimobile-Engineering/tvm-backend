<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumHireManifest extends Model
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

    public function premiumHireBooking()
    {
        return $this->belongsTo(PremiumHireBooking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
