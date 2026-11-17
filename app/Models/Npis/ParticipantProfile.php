<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $registration_id
 * @property string $full_name
 * @property string $full_name_normalized
 * @property string|null $sector
 * @property string|null $rank_code
 * @property string|null $state_code
 * @property string|null $command_id
 * @property string|null $unit_id
 * @property string|null $unit_other_name
 * @property string|null $organization
 * @property string|null $organization_normalized
 * @property string|null $designation
 * @property string|null $vendor_service
 * @property string|null $vendor_service_other
 * @property-read Registration $registration
 * @property-read Rank|null $rank
 * @property-read State|null $state
 * @property-read Command|null $command
 * @property-read Unit|null $unit
 */
class ParticipantProfile extends Model
{
    protected $table = 'npis_participant_profiles';

    protected $fillable = [
        'registration_id',
        'full_name',
        'full_name_normalized',
        'sector',
        'rank_code',
        'state_code',
        'command_id',
        'unit_id',
        'unit_other_name',
        'organization',
        'organization_normalized',
        'designation',
        'vendor_service',
        'vendor_service_other',
    ];

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * @return BelongsTo<Rank, $this>
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'rank_code');
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_code');
    }

    /**
     * @return BelongsTo<Command, $this>
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class, 'command_id');
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
