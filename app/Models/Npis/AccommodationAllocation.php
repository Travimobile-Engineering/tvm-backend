<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function request(): BelongsTo
    {
        return $this->belongsTo(AccommodationRequest::class, 'accommodation_request_id');
    }
}
