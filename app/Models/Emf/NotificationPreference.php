<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $officer_id
 * @property array<string, mixed> $preferences
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 */
class NotificationPreference extends Model
{
    protected $table = 'emf_notification_preferences';

    protected $fillable = ['officer_id', 'preferences'];

    protected function casts(): array
    {
        return ['preferences' => 'array'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
