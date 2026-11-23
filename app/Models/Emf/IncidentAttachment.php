<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $incident_id
 * @property string $attachment_type
 * @property string $file_url
 * @property int|null $file_size
 * @property int|null $duration_seconds
 * @property string|null $uploaded_by
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Incident|null $incident
 * @property-read Officer|null $uploader
 */
class IncidentAttachment extends Model
{
    protected $table = 'emf_incident_attachments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'incident_id', 'attachment_type', 'file_url', 'file_size', 'duration_seconds', 'uploaded_by', 'metadata'];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'duration_seconds' => 'integer', 'metadata' => 'array'];
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'uploaded_by');
    }
}
