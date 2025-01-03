<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripBooking extends Model
{
    protected $guarded = [];
    protected $hidden = ['id'];

    public function getRouteKeyName(){
        return 'booking_id';
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trip(){
        return $this->belongsTo(Trip::class, 'trip_id', 'uuid');
    }
}
