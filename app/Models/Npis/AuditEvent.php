<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $actor_type
 * @property string|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_role
 * @property string $action
 * @property string $target_type
 * @property string|null $target_id
 * @property string $summary
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip
 * @property Carbon $occurred_at
 */
class AuditEvent extends Model
{
    protected $table = 'npis_audit_events';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'summary',
        'metadata',
        'ip',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
