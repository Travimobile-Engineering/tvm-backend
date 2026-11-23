<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $manifest_number
 * @property string $mode
 * @property string $operator
 * @property string|null $vehicle_registration
 * @property string|null $driver_name
 * @property string $route_origin
 * @property string $route_destination
 * @property int|null $seats
 * @property int $passenger_count
 * @property int $checked_count
 * @property string|null $duty_point_id
 * @property string|null $state_code
 * @property string|null $lga_id
 * @property string $verdict
 * @property Carbon|null $departed_at
 * @property Carbon|null $expected_arrival_at
 * @property string|null $checked_by_officer_id
 * @property Carbon|null $checked_at
 * @property string $source
 * @property string|null $notes
 * @property string $arrival_status
 * @property Carbon|null $cleared_at
 * @property string|null $clearance_reference
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ManifestPassenger> $passengers
 * @property-read Collection<int, ManifestCheck> $checks
 * @property-read Collection<int, FieldCheck> $fieldChecks
 * @property-read Collection<int, FieldManifestEvent> $trackingEvents
 * @property-read DutyPoint|null $dutyPoint
 * @property-read Officer|null $officer
 */
class Manifest extends Model
{
    protected $table = 'emf_manifests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'manifest_number', 'mode', 'operator', 'vehicle_registration', 'driver_name', 'route_origin', 'route_destination', 'seats', 'passenger_count', 'checked_count', 'duty_point_id', 'state_code', 'lga_id', 'verdict', 'departed_at', 'expected_arrival_at', 'checked_by_officer_id', 'checked_at', 'source', 'notes', 'arrival_status', 'cleared_at', 'clearance_reference'];

    protected function casts(): array
    {
        return ['seats' => 'integer', 'passenger_count' => 'integer', 'checked_count' => 'integer', 'departed_at' => 'datetime', 'expected_arrival_at' => 'datetime', 'checked_at' => 'datetime', 'cleared_at' => 'datetime'];
    }

    /**
     * @return HasMany<ManifestPassenger, $this>
     */
    public function passengers(): HasMany
    {
        return $this->hasMany(ManifestPassenger::class, 'manifest_id');
    }

    /**
     * @return HasMany<ManifestCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(ManifestCheck::class, 'manifest_id');
    }

    /**
     * @return BelongsTo<DutyPoint, $this>
     */
    public function dutyPoint(): BelongsTo
    {
        return $this->belongsTo(DutyPoint::class, 'duty_point_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'checked_by_officer_id');
    }

    /**
     * @return HasMany<FieldCheck, $this>
     */
    public function fieldChecks(): HasMany
    {
        return $this->hasMany(FieldCheck::class, 'manifest_id');
    }

    /**
     * @return HasMany<FieldManifestEvent, $this>
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(FieldManifestEvent::class, 'manifest_id');
    }
}
