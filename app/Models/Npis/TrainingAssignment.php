<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $registration_id
 * @property string $session_id
 * @property Carbon $assigned_at
 * @property int|null $assigned_by_admin_id
 * @property-read Registration $registration
 * @property-read TrainingSession $session
 */
class TrainingAssignment extends Model
{
    protected $table = 'npis_training_assignments';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'registration_id',
        'session_id',
        'assigned_at',
        'assigned_by_admin_id',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * @return BelongsTo<TrainingSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }
}
