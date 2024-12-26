<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'uuid',
        'vehicle_id',
        'transit_company_id'.
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
        'price',
        'bus_stops',
        'type',
        'reason',
        'date_cancelled',
        'status',
        'means',
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

    public function tripBookings()
    {
        return $this->hasMany(TripBooking::class, 'trip_id');
    }

    protected $hidden = ['id', 'departure', 'destination'];
    public function getRouteKeyName(){
        return 'trip_id';
    }
}
