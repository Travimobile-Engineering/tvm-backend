<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'user_id',
        'uuid',
        'vehicle_id',
        'transit_company_id',
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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($trip) {
            $trip->uuid = getRandomNumber();
        });
        static::retrieved(function($model){
            $model->from = getRouteStateAndTownNameFromTownId($model->departure);
            $model->to = getRouteStateAndTownNameFromTownId($model->destination);
        });
    }

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

    public function manifests()
    {
        return $this->hasMany(Manifest::class, 'trip_id');
    }

    protected $hidden = ['id'];
    public function getRouteKeyName(){
        return 'uuid';
    }
}
