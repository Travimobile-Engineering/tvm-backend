<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $subscriber_name
 * @property string $agency
 * @property string $scope
 * @property array<int, string>|null $modes
 * @property bool $notify_email
 * @property string|null $officer_id
 * @property string $status
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 */
class WatchlistSubscription extends Model
{
    protected $table = 'emf_watchlist_subscriptions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'subscriber_name', 'agency', 'scope', 'modes', 'notify_email', 'officer_id', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['modes' => 'array', 'notify_email' => 'boolean'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
