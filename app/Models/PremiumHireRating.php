<?php

namespace App\Models;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;

class PremiumHireRating extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'rating',
        'comment',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
