<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manifest extends Model
{
    protected $fillable = [
        'trip_id',
        'booking_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'next_of_kin',
        'next_of_kin_phone',
        'seat',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
