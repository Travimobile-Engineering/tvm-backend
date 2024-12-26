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
        'reoccur_duration',
        'start_date',
        'end_date',
        'trip_days',
        'bus_type',
        'ticket_price',
        'bus_stops',
        'type',
        'reason',
        'date_cancelled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'trip_days' => 'array',
            'bus_stops' => 'array',
            'date_cancelled' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
