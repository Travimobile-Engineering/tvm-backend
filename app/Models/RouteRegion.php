<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteRegion extends Model
{
    use HasFactory;

    protected $table = 'route_regions';

    protected $fillable = [
        'name',
    ];
}
