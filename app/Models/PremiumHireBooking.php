<?php

namespace App\Models;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;

class PremiumHireBooking extends Model
{
    protected $fillable = [
        'user_id',
        'uuid',
        'driver_id',
        'vehicle_id',
        'payment_log_id',
        'ticket_type',
        'lng',
        'lat',
        'bus_stops',
        'luggage',
        'amount',
        'payment_type',
        'payment_status',
        'payment_method',
        'date',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'bus_stops' => 'array',
            'luggage' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function premiumHireManifests()
    {
        return $this->hasMany(PremiumHireManifest::class);
    }

    public function premiumHireBookingPassengers()
    {
        return $this->hasMany(PremiumHireBookingPassenger::class);
    }
}
