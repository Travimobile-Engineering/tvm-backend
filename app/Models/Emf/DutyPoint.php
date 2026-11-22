<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $state_code
 * @property string|null $lga_id
 * @property string $name
 * @property string $type
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $shift_start
 * @property string $shift_end
 * @property int $officers_count
 * @property int $grants_count
 * @property string $status
 * @property int|null $conflict_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lga|null $lga
 * @property-read State|null $state
 * @property-read Collection<int, DutyRoster> $roster
 */
class DutyPoint extends Model
{
    protected $table = 'emf_duty_points';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'state_code', 'lga_id', 'name', 'type', 'address', 'latitude', 'longitude', 'shift_start', 'shift_end', 'officers_count', 'grants_count', 'status'];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'officers_count' => 'integer', 'grants_count' => 'integer'];
    }

    /**
     * @return BelongsTo<Lga, $this>
     */
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lga_id');
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_code');
    }

    /**
     * @return HasMany<DutyRoster, $this>
     */
    public function roster(): HasMany
    {
        return $this->hasMany(DutyRoster::class, 'duty_point_id');
    }
}
