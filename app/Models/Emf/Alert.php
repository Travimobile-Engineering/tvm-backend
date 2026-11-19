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
 * @property string $alert_number
 * @property string $type
 * @property string $title
 * @property string|null $summary
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $manifest_id
 * @property string|null $watchlist_entry_id
 * @property int|null $confidence
 * @property string|null $mode
 * @property string|null $route
 * @property string|null $duty_point_id
 * @property string|null $state_code
 * @property string|null $lga_id
 * @property string $status
 * @property int $sla_seconds
 * @property Carbon $detected_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $acknowledged_at
 * @property string|null $assigned_officer_id
 * @property string|null $responder_name
 * @property string|null $resolution
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manifest|null $manifest
 * @property-read WatchlistEntry|null $watchlistEntry
 * @property-read Collection<int, AlertAction> $actions
 * @property-read Incident|null $incident
 * @property-read Officer|null $assignee
 */
class Alert extends Model
{
    protected $table = 'emf_alerts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'alert_number', 'type', 'title', 'summary', 'subject_type', 'subject_id', 'manifest_id', 'watchlist_entry_id', 'confidence', 'mode', 'route', 'duty_point_id', 'state_code', 'lga_id', 'status', 'sla_seconds', 'detected_at', 'verified_at', 'acknowledged_at', 'assigned_officer_id', 'responder_name', 'resolution', 'metadata'];

    protected function casts(): array
    {
        return ['confidence' => 'integer', 'sla_seconds' => 'integer', 'detected_at' => 'datetime', 'verified_at' => 'datetime', 'acknowledged_at' => 'datetime', 'metadata' => 'array'];
    }

    /**
     * @return BelongsTo<Manifest, $this>
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class, 'manifest_id');
    }

    /**
     * @return BelongsTo<WatchlistEntry, $this>
     */
    public function watchlistEntry(): BelongsTo
    {
        return $this->belongsTo(WatchlistEntry::class, 'watchlist_entry_id');
    }

    /**
     * @return HasMany<AlertAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(AlertAction::class, 'alert_id');
    }

    /**
     * @return HasOne<Incident, $this>
     */
    public function incident(): HasOne
    {
        return $this->hasOne(Incident::class, 'alert_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'assigned_officer_id');
    }
}
