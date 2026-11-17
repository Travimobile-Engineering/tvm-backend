<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $accommodation_request_id
 * @property string $hotel_name
 * @property string $room_type
 * @property Carbon $confirmed_at
 * @property int|null $allocated_by_admin_id
 * @property-read AccommodationRequest $request
 */
class AccommodationAllocation extends Model
{
    protected $table = 'npis_accommodation_allocations';

    protected $fillable = [
        'accommodation_request_id',
        'hotel_name',
        'room_type',
        'confirmed_at',
        'allocated_by_admin_id',
    ];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<AccommodationRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AccommodationRequest::class, 'accommodation_request_id');
    }
}
