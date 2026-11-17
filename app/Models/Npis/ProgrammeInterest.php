<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $registration_id
 * @property string $track
 * @property list<string> $transport_subsectors
 * @property-read Registration $registration
 */
class ProgrammeInterest extends Model
{
    protected $table = 'npis_programme_interests';

    protected $fillable = ['registration_id', 'track', 'transport_subsectors'];

    protected function casts(): array
    {
        return ['transport_subsectors' => 'array'];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
