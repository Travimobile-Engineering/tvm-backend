<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRouteManagement extends Model
{
    protected $fillable = [
        'park_name',
        'address',
        'state',
        'zone',
        'originating_route',
        'terminating_route',
        'estimated_trip',
        'key_man',
        'estimated_distance',
        'estimated_time',
        'cost_of_transportation',
        'road_safety_rating',
        'field_officer',
        'occasioned_by',
    ];

    protected $casts = [
        'occasioned_by' => 'array',
    ];

    protected $hidden = [
        'created_at',
    ];
}
