<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $field_check_id
 * @property string $manifest_passenger_id
 * @property string|null $seat
 * @property string $status
 * @property string|null $reason
 * @property string|null $note
 * @property array<string, mixed>|null $identity_snapshot
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $flagged_at
 * @property Carbon|null $resolved_at
 * @property string|null $resolved_by
 * @property string|null $alert_id
 * @property string|null $incident_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FieldCheck|null $fieldCheck
 * @property-read ManifestPassenger|null $passenger
 * @property-read Alert|null $alert
 * @property-read Incident|null $incident
 * @property-read Officer|null $resolver
 */
class FieldCheckSeat extends Model
{
    protected $table = 'emf_field_check_seats';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'field_check_id', 'manifest_passenger_id', 'seat', 'status', 'reason', 'note',
        'identity_snapshot', 'confirmed_at', 'flagged_at', 'resolved_at', 'resolved_by', 'alert_id', 'incident_id',
    ];

    protected function casts(): array
    {
        return [
            'identity_snapshot' => 'array',
            'confirmed_at' => 'datetime',
            'flagged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FieldCheck, $this>
     */
    public function fieldCheck(): BelongsTo
    {
        return $this->belongsTo(FieldCheck::class, 'field_check_id');
    }

    /**
     * @return BelongsTo<ManifestPassenger, $this>
     */
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(ManifestPassenger::class, 'manifest_passenger_id');
    }

    /**
     * @return BelongsTo<Alert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'resolved_by');
    }
}
