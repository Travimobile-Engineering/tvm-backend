<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $incident_id
 * @property string $event_type
 * @property string $description
 * @property string|null $actor_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Incident|null $incident
 */
class IncidentEvent extends Model
{
    protected $table = 'emf_incident_events';

    protected $fillable = ['incident_id', 'event_type', 'description', 'actor_name'];

    protected function casts(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id');
    }
}
