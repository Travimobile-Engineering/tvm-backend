<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'departure',
        'destination',
        'departure_date',
        'departure_time',
        'repeat_trip',
        'start_date',
        'trip_days',
        'bus_type',
        'ticket_price',
        'bus_stops',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'trip_days' => 'json',
            'bus_stops' => 'json',
        ];
    }
}
