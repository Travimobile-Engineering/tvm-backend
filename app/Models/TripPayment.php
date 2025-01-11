<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripPayment extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'driver_id',
        'amount',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }
}
