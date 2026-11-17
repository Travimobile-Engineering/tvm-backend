<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgrammeInterest extends Model
{
    protected $table = 'npis_programme_interests';

    protected $fillable = ['registration_id', 'track', 'transport_subsectors'];

    protected function casts(): array
    {
        return ['transport_subsectors' => 'array'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
