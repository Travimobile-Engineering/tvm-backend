<?php

namespace App\Models;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;

class VehicleImage extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'url',
        'public_id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
