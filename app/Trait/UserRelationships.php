<?php

namespace App\Trait;

use App\Models\Trip;
use App\Models\Transport;
use App\Models\TripBooking;
use App\Models\TransitCompany;

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




