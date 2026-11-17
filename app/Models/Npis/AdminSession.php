<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(OrganizerAccessGrant::class, 'access_grant_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at->isFuture()
            && $this->grant?->isActive();
    }
}
