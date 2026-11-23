<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $officer_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 */
class OfficerSession extends Model
{
    protected $table = 'emf_officer_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'officer_id', 'token_hash', 'expires_at', 'revoked_at', 'ip', 'user_agent'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
