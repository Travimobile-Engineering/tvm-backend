<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $check_reference
 * @property string $manifest_id
 * @property string $officer_id
 * @property string|null $duty_point_id
 * @property string $status
 * @property Carbon $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $hold_at
 * @property Carbon|null $hold_acknowledged_at
 * @property Carbon|null $released_at
 * @property string|null $hold_reason
 * @property string|null $ops_alert_reference
 * @property string|null $clearance_reference
 * @property string|null $incident_id
 * @property int $listed_count
 * @property int $confirmed_count
 * @property int $flagged_count
 * @property int $unverified_count
 * @property int $cleared_with_note_count
 * @property bool $vehicle_cleared
 * @property string|null $notes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manifest|null $manifest
 * @property-read Officer|null $officer
 * @property-read DutyPoint|null $dutyPoint
 * @property-read Incident|null $incident
 * @property-read Collection<int, FieldCheckSeat> $seats
 */
class FieldCheck extends Model
{
    protected $table = 'emf_field_checks';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'check_reference', 'manifest_id', 'officer_id', 'duty_point_id', 'status',
        'started_at', 'completed_at', 'hold_at', 'hold_acknowledged_at', 'released_at',
        'hold_reason', 'ops_alert_reference', 'clearance_reference', 'incident_id',
        'listed_count', 'confirmed_count', 'flagged_count', 'unverified_count',
        'cleared_with_note_count', 'vehicle_cleared', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'hold_at' => 'datetime',
            'hold_acknowledged_at' => 'datetime',
            'released_at' => 'datetime',
            'listed_count' => 'integer',
            'confirmed_count' => 'integer',
            'flagged_count' => 'integer',
            'unverified_count' => 'integer',
            'cleared_with_note_count' => 'integer',
            'vehicle_cleared' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Manifest, $this>
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class, 'manifest_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }

    /**
     * @return BelongsTo<DutyPoint, $this>
     */
    public function dutyPoint(): BelongsTo
    {
        return $this->belongsTo(DutyPoint::class, 'duty_point_id');
    }

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id');
    }

    /**
     * @return HasMany<FieldCheckSeat, $this>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(FieldCheckSeat::class, 'field_check_id');
    }
}
