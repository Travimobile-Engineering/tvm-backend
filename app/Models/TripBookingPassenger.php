<?php

namespace App\Models;

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
        ];
    }

    public function tripBooking()
    {
        return $this->belongsTo(TripBooking::class);
    }
}
