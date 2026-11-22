<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $entry_number
 * @property string $type
 * @property string $risk
 * @property string $reason
 * @property string $source
 * @property string|null $full_name
 * @property string|null $nin
 * @property string|null $phone
 * @property string|null $plate_number
 * @property Carbon|null $dob
 * @property string $nationality
 * @property string|null $address
 * @property Carbon|null $expires_at
 * @property string $status
 * @property string|null $notes
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WatchlistDocument> $documents
 * @property-read Collection<int, Alert> $alerts
 */
class WatchlistEntry extends Model
{
    protected $table = 'emf_watchlist_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'entry_number', 'type', 'risk', 'reason', 'source', 'full_name', 'nin', 'phone', 'plate_number', 'dob', 'nationality', 'address', 'expires_at', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['dob' => 'date:Y-m-d', 'expires_at' => 'datetime'];
    }

    /**
     * @return HasMany<WatchlistDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(WatchlistDocument::class, 'entry_id');
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'watchlist_entry_id');
    }
}
