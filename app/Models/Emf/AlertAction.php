<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $alert_id
 * @property string $action
 * @property string|null $note
 * @property string|null $reason
 * @property string|null $actor_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Alert|null $alert
 * @property-read Officer|null $actor
 */
class AlertAction extends Model
{
    protected $table = 'emf_alert_actions';

    protected $fillable = ['alert_id', 'action', 'note', 'reason', 'actor_id', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'actor_id');
    }
}
