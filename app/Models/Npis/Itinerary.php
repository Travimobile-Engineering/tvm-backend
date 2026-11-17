<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $registration_id
 * @property string $travel_mode
 * @property Carbon|null $arrival_date
 * @property string|null $arrival_time
 * @property string|null $arrival_time_window
 * @property string $arrival_plan_status
 * @property Carbon|null $departure_date
 * @property string|null $departure_time
 * @property string|null $departure_time_window
 * @property string $departure_plan_status
 * @property string $post_summit_plan
 * @property string|null $departure_airport_code
 * @property string|null $flight_time
 * @property string|null $flight_time_window
 * @property string|null $flight_number
 * @property string $timezone
 * @property-read Registration $registration
 */
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

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
