<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'rank_code');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_code');
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class, 'command_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
