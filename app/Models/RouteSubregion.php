<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteSubregion extends Model
{
    use HasFactory;

    protected $table = "route_subregions";

    protected $fillable = [
        'name',
        'state_id',
        'region_id',
        'status'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function region()
    {
        return $this->belongsTo(RouteRegion::class, 'region_id');
    }

    public function parks()
    {
        return $this->hasMany(Park::class, 'route_subregion_id');
    }
}
