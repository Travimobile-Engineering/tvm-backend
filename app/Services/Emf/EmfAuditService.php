<?php

namespace App\Services\Emf;

use App\Models\Emf\AuditEvent;
use App\Models\Emf\Officer;
use Illuminate\Http\Request;

class EmfAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        ?Officer $actor,
        string $action,
        string $targetType,
        ?string $targetId,
        string $detail,
        string $result = 'done',
        array $metadata = [],
        ?Request $request = null,
    ): AuditEvent {
        return AuditEvent::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor->full_name ?? 'System',
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'detail' => $detail,
            'result' => $result,
            'ip' => $request?->ip(),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
