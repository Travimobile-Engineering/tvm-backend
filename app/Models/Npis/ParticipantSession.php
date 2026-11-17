<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $contact_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property-read VerifiedContact $contact
 */
class ParticipantSession extends Model
{
    protected $table = 'npis_participant_sessions';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'contact_id', 'token_hash', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VerifiedContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(VerifiedContact::class, 'contact_id');
    }
}
