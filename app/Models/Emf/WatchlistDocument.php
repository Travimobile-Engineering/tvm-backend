<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $entry_id
 * @property string $name
 * @property string $file_url
 * @property int|null $file_size
 * @property string|null $uploaded_by
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WatchlistEntry|null $entry
 */
class WatchlistDocument extends Model
{
    protected $table = 'emf_watchlist_documents';

    protected $fillable = ['entry_id', 'name', 'file_url', 'file_size', 'uploaded_by', 'metadata'];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'metadata' => 'array'];
    }

    /**
     * @return BelongsTo<WatchlistEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(WatchlistEntry::class, 'entry_id');
    }
}
