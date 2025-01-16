<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Park extends Model
{
    protected $fillable = ['route_subregion_id', 'name'];

    public function routeSubregion(): BelongsTo
    {
        return $this->belongsTo(RouteSubregion::class, 'route_subregion_id');
    }
}
