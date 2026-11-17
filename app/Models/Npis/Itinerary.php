<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Itinerary extends Model
{
    protected $table = 'npis_itineraries';

    protected $fillable = [
        'registration_id',
        'travel_mode',
        'arrival_date',
        'arrival_time',
        'arrival_time_window',
        'arrival_plan_status',
        'departure_date',
        'departure_time',
        'departure_time_window',
        'departure_plan_status',
        'post_summit_plan',
        'departure_airport_code',
        'flight_time',
        'flight_time_window',
        'flight_number',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'arrival_date' => 'date:Y-m-d',
            'departure_date' => 'date:Y-m-d',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
