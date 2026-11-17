<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $registration_id
 * @property string $summit_tag_number
 * @property Carbon $event_date
 * @property Carbon $checked_in_at
 * @property string $method
 * @property int|null $recorded_by_admin_id
 * @property-read Registration $registration
 */
class CheckIn extends Model
{
    protected $table = 'npis_check_ins';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'registration_id',
        'summit_tag_number',
        'event_date',
        'checked_in_at',
        'method',
        'recorded_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
