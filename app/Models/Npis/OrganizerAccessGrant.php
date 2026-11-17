<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property string|null $ip
 * @property string|null $user_agent
 * @property-read Collection<int, AdminSession> $sessions
 */
class OrganizerAccessGrant extends Model
{
    protected $table = 'npis_organizer_access_grants';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AdminSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(AdminSession::class, 'access_grant_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
