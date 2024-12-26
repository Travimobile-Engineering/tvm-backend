<?php

namespace App\Trait;

use App\Models\Transport;
use App\Models\TripBooking;

trait UserRelationships
{
    public function transports()
    {
        return $this->hasMany(Transport::class, 'user_id');
    }

    public function tripBookings()
    {
        return $this->hasMany(TripBooking::class, 'user_id');
    }
}




