<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $actor_id
 * @property string $actor_name
 * @property string $action
 * @property string $target_type
 * @property string|null $target_id
 * @property string|null $detail
 * @property string $result
 * @property string|null $ip
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AuditEvent extends Model
{
    protected $table = 'emf_audit_events';

    protected $fillable = ['actor_id', 'actor_name', 'action', 'target_type', 'target_id', 'detail', 'result', 'ip', 'metadata', 'occurred_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }
}
