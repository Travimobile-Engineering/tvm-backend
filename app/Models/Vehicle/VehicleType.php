<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'rows',
        'columns',
    ];

    public function getTotalSeatsAttribute()
    {
        return $this->rows * $this->columns;
    }
}
