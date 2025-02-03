<?php

namespace App\Models;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;

class TripSchedule extends Model
{
    protected $fillable = [
        'vehicle_id',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
