<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $state_code
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read State|null $state
 */
class Lga extends Model
{
    protected $table = 'emf_lgas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'state_code', 'name'];

    protected function casts(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_code');
    }
}
