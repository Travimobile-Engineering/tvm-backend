<?php

namespace App\Models;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;

class PreferredLocation extends Model
{
    protected $fillable = [
        'vehicle_id',
        'route_id'
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function subRegion()
    {
        return $this->belongsTo(RouteSubregion::class, 'route_id');
    }
}
