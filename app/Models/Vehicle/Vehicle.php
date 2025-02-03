<?php

namespace App\Models\Vehicle;

use App\Models\PreferredLocation;
use App\Models\PremiumUpgrade;
use App\Models\TripSchedule;
use App\Models\UnavailableDate;
use App\Models\User;
use App\Models\VehicleImage;
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
        'seat_column',
        'description',
        'management_type',
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

    public function preferredLocations()
    {
        return $this->hasMany(PreferredLocation::class);
    }

    public function tripSchedule()
    {
        return $this->hasOne(TripSchedule::class);
    }

    public function unavailableDates()
    {
        return $this->hasMany(UnavailableDate::class);
    }

    public function vehicleImages()
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function premiumUpgrades()
    {
        return $this->hasMany(PremiumUpgrade::class);
    }
}
