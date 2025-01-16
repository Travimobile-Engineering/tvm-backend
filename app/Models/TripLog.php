<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripLog extends Model
{
    protected $fillable = [
        'user_id',
        'trip_id',
        'amount_charged',
        'retry_attempt',
        'message',
        'status',
    ];
}
