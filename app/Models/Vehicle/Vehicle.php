<?php

namespace App\Models\Vehicle;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable  = [
        'name',
        'company_id',
        'user_id',
        'brand_id',
        'ac',
        'plate_no',
        'engine_no',
        'chassis_no',
        'type',
        'capacity',
        'year',
        'color',
        'model',
        'air_conditioned',
        'seats',
        'seat_row',
        'seat_column'
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
