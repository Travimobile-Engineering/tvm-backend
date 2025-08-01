<?php

namespace App\Models;

use App\Enum\TripStatus;
use App\Models\Vehicle\Vehicle;
use App\Trait\ClearsResponseCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory, ClearsResponseCache;

    protected $fillable = [
        'user_id',
        'agent_id',
        'uuid',
        'vehicle_id',
        'transit_company_id',
        'departure',
        'destination',
        'departure_date',
        'departure_time',
        'repeat_trip',
        'reoccur_duration',
        'start_date',
        'end_date',
        'trip_days',
        'bus_type',
        'price',
        'bus_stops',
        'type',
        'reason',
        'date_cancelled',
        'status',
        'means',
        'trip_duration',
        'trip_schedule',
        'departure_park',
        'destination_park',
        'zone_id',
    ];

    protected $hidden = [];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($trip) {
            $trip->uuid = getRandomNumber();
        });

        static::retrieved(function($model){
            $model->from = getRouteStateAndTownNameFromTownId($model->departure);
            $model->to = getRouteStateAndTownNameFromTownId($model->destination);
        });
    }

    protected function casts(): array
    {
        return [
            'trip_days' => 'array',
            'trip_schedule' => 'array',
            'bus_stops' => 'array',
            'date_cancelled' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function tripBookings()
    {
        return $this->hasMany(TripBooking::class, 'trip_id');
    }

    public function manifest()
    {
        return $this->hasOne(Manifest::class, 'trip_id');
    }

    public function departureRegion()
    {
        return $this->belongsTo(RouteSubregion::class, 'departure');
    }

    public function destinationRegion()
    {
        return $this->belongsTo(RouteSubregion::class, 'destination');
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class, 'trip_id');
    }

    public function transitCompany()
    {
        return $this->belongsTo(TransitCompany::class);
    }

    public static function hasOngoingTrip($id, $userId)
    {
        return self::where('id', $id)
            ->where('user_id', $userId)
            ->where('status', TripStatus::INPROGRESS)
            ->exists();
    }

}
