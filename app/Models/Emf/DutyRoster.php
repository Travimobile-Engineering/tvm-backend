<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $duty_point_id
 * @property Carbon $roster_date
 * @property string $shift
 * @property string|null $officer_id
 * @property string $status
 * @property bool $published
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DutyPoint|null $dutyPoint
 * @property-read Officer|null $officer
 */
class DutyRoster extends Model
{
    protected $table = 'emf_duty_roster';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'duty_point_id', 'roster_date', 'shift', 'officer_id', 'status', 'published', 'created_by'];

    protected function casts(): array
    {
        return ['roster_date' => 'date:Y-m-d', 'published' => 'boolean'];
    }

    /**
     * @return BelongsTo<DutyPoint, $this>
     */
    public function dutyPoint(): BelongsTo
    {
        return $this->belongsTo(DutyPoint::class, 'duty_point_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
