<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'session_id');
    }

    public function assignedCount(): int
    {
        return $this->assignments()->count();
    }
}
