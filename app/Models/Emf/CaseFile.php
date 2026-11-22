<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $case_number
 * @property string $title
 * @property string|null $incident_id
 * @property string $status
 * @property string $severity
 * @property string|null $owner_officer_id
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $due_date
 * @property string|null $summary
 * @property string $approval_status
 * @property string|null $approved_by
 * @property Carbon|null $approved_at
 * @property string $share_status
 * @property array<int, mixed>|null $linked_records
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $owner
 * @property-read Collection<int, CaseEvent> $events
 * @property-read Collection<int, CaseEvidence> $evidence
 */
class CaseFile extends Model
{
    protected $table = 'emf_cases';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'case_number', 'title', 'incident_id', 'status', 'severity', 'owner_officer_id', 'opened_at', 'closed_at', 'due_date', 'summary', 'approval_status', 'approved_by', 'approved_at', 'share_status', 'linked_records', 'metadata'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'due_date' => 'date:Y-m-d', 'approved_at' => 'datetime', 'linked_records' => 'array', 'metadata' => 'array'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'owner_officer_id');
    }

    /**
     * @return HasMany<CaseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class, 'case_id');
    }

    /**
     * @return HasMany<CaseEvidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(CaseEvidence::class, 'case_id');
    }
}
