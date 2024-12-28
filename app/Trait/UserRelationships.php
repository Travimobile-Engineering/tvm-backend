<?php

namespace App\Trait;

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
}




