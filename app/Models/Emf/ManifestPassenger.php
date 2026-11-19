<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $manifest_id
 * @property string $full_name
 * @property string|null $nin
 * @property string|null $seat
 * @property string $verdict
 * @property string|null $watchlist_entry_id
 * @property Carbon|null $checked_at
 * @property int|null $identity_confidence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manifest|null $manifest
 * @property-read WatchlistEntry|null $watchlistEntry
 */
class ManifestPassenger extends Model
{
    protected $table = 'emf_manifest_passengers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'manifest_id', 'full_name', 'nin', 'seat', 'verdict', 'watchlist_entry_id', 'checked_at', 'identity_confidence'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime', 'identity_confidence' => 'integer'];
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
}
