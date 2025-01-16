<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteSubregion extends Model
{
    protected $table = "route_subregions";

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function parks()
    {
        return $this->hasMany(Park::class, 'route_subregion_id');
    }
}
