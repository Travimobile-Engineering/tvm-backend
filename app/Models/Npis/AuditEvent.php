<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

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
