<?php

namespace App\Trait;

use App\Models\BusStop;
use App\Models\Document;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\TransitCompany;
use App\Models\Transport;
use App\Models\Trip;
use App\Models\TripBooking;

trait UserRelationships
{
    public function trips()
    {
        return $this->hasMany(Trip::class, 'user_id');
    }

    public function tripBookings()
    {
        return $this->hasMany(TripBooking::class, 'user_id');
    }

    public function transitCompany()
    {
        return $this->belongsTo(TransitCompany::class, 'user_id');
    }

    public function driverVehicle()
    {
        return $this->hasOne(DriverVehicle::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    public function busStops()
    {
        return $this->hasMany(BusStop::class, 'user_id');
    }
}




