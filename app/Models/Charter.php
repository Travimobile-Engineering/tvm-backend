<?php

namespace App\Models;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;

class Charter extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'number_of_vehicles',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
