<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $manifest_id
 * @property string $event_type
 * @property string $location
 * @property Carbon $logged_at
 * @property string $source
 * @property string|null $note
 * @property string|null $actor_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manifest|null $manifest
 * @property-read Officer|null $actor
 */
class FieldManifestEvent extends Model
{
    protected $table = 'emf_field_manifest_events';

    protected $fillable = ['manifest_id', 'event_type', 'location', 'logged_at', 'source', 'note', 'actor_id', 'metadata'];

    protected function casts(): array
    {
        return ['logged_at' => 'datetime', 'metadata' => 'array'];
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'actor_id');
    }
}
