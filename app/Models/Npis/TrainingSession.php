<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $event_id
 * @property string $title
 * @property string $track
 * @property string|null $subsector
 * @property Carbon $session_date
 * @property string $start_time
 * @property string $end_time
 * @property string $room
 * @property int $capacity
 * @property-read Collection<int, TrainingAssignment> $assignments
 */
class TrainingSession extends Model
{
    use SoftDeletes;

    protected $table = 'npis_training_sessions';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'event_id',
        'title',
        'track',
        'subsector',
        'session_date',
        'start_time',
        'end_time',
        'room',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date:Y-m-d',
            'capacity' => 'integer',
        ];
    }

    /**
     * @return HasMany<TrainingAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'session_id');
    }

    public function assignedCount(): int
    {
        return $this->assignments()->count();
    }
}
