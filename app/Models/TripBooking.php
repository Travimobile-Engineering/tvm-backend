<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripBooking extends Model
{
    protected $guarded = [];

    public function getRouteKeyName(){
        return 'ticket_id';
    }
}
