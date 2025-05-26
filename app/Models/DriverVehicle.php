<?php

namespace App\Models;

use App\Facades\UserFacade;
use Illuminate\Database\Eloquent\Model;

class DriverVehicle extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_year',
        'vehicle_model',
        'vehicle_color',
        'plate_number',
        'vehicle_type',
        'vehicle_capacity',
        'seats',
        'seat_row',
        'seat_column',
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'array',
        ];
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

    public function getUserAttribute()
    {
        return UserFacade::find($this->user_id);
    }
}
