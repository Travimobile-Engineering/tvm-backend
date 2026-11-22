<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $incident_number
 * @property string $title
 * @property string $type
 * @property string $severity
 * @property string $status
 * @property string|null $summary
 * @property string|null $manifest_id
 * @property string|null $alert_id
 * @property string|null $watchlist_entry_id
 * @property string $location
 * @property string|null $duty_point_id
 * @property string|null $state_code
 * @property string|null $lga_id
 * @property string|null $reporter_name
 * @property string|null $reported_by
 * @property string|null $responder_officer_id
 * @property Carbon $reported_at
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $resolved_at
 * @property array<string, mixed>|null $metadata
 * @property string|null $category
 * @property string $report_state
 * @property string|null $client_report_id
 * @property Carbon|null $signed_at
 * @property string|null $signed_by
 * @property string|null $gps_latitude
 * @property string|null $gps_longitude
 * @property string|null $linked_vehicle
 * @property Carbon|null $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manifest|null $manifest
 * @property-read Alert|null $alert
 * @property-read Officer|null $responder
 * @property-read Collection<int, IncidentEvent> $events
 * @property-read Collection<int, IncidentAttachment> $attachments
 * @property-read CaseFile|null $case
 */
class Incident extends Model
{
    protected $table = 'emf_incidents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'incident_number', 'title', 'type', 'severity', 'status', 'summary', 'manifest_id', 'alert_id', 'watchlist_entry_id', 'location', 'duty_point_id', 'state_code', 'lga_id', 'reporter_name', 'reported_by', 'responder_officer_id', 'reported_at', 'acknowledged_at', 'resolved_at', 'metadata', 'category', 'report_state', 'client_report_id', 'signed_at', 'signed_by', 'gps_latitude', 'gps_longitude', 'linked_vehicle', 'occurred_at'];

    protected function casts(): array
    {
        return ['reported_at' => 'datetime', 'acknowledged_at' => 'datetime', 'resolved_at' => 'datetime', 'metadata' => 'array', 'signed_at' => 'datetime', 'occurred_at' => 'datetime', 'gps_latitude' => 'decimal:7', 'gps_longitude' => 'decimal:7'];
    }

    /**
     * @return BelongsTo<Manifest, $this>
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class, 'manifest_id');
    }

    /**
     * @return BelongsTo<Alert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'responder_officer_id');
    }

    /**
     * @return HasMany<IncidentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(IncidentEvent::class, 'incident_id');
    }

    /**
     * @return HasMany<IncidentAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class, 'incident_id');
    }

    /**
     * @return HasOne<CaseFile, $this>
     */
    public function case(): HasOne
    {
        return $this->hasOne(CaseFile::class, 'incident_id');
    }
}
