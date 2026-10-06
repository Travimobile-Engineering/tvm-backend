<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripBookingPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_booking_id',
        'name',
        'email',
        'phone_number',
        'next_of_kin',
        'next_of_kin_phone_number',
        'gender',
        'selected_seat',
        'on_seat',
    ];

    protected function casts(): array
    {
        return [
            'on_seat' => 'boolean',
            'email' => EncryptedAttribute::class,
            'phone_number' => EncryptedAttribute::class,
            'next_of_kin' => EncryptedAttribute::class,
            'next_of_kin_phone_number' => EncryptedAttribute::class,
        ];
    }

    public function tripBooking()
    {
        return $this->belongsTo(TripBooking::class);
    }
}
