<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $token_hash
 * @property int $admin_user_id
 * @property string|null $access_grant_id
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_seen_at
 * @property string|null $ip
 * @property string|null $user_agent
 * @property-read AdminUser $admin
 * @property-read OrganizerAccessGrant $grant
 */
class AdminSession extends Model
{
    protected $table = 'npis_admin_sessions';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'token_hash',
        'admin_user_id',
        'access_grant_id',
        'expires_at',
        'revoked_at',
        'last_seen_at',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * @return BelongsTo<OrganizerAccessGrant, $this>
     */
    public function grant(): BelongsTo
    {
        return $this->belongsTo(OrganizerAccessGrant::class, 'access_grant_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at->isFuture()
            && ($this->access_grant_id === null || $this->grant?->isActive());
    }
}
