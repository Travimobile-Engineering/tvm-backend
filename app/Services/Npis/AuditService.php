<?php

namespace App\Services\Npis;

use App\Models\Npis\AdminUser;
use App\Models\Npis\AuditEvent;
use App\Models\Npis\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        AdminUser|Registration|array|null $actor,
        string $action,
        string $targetType,
        ?string $targetId,
        string $summary,
        array $metadata = [],
        ?Request $request = null,
    ): AuditEvent {
        $actorType = 'system';
        $actorId = null;
        $actorName = 'System';
        $actorRole = null;

        if ($actor instanceof AdminUser) {
            $actorType = 'organizer';
            $actorId = (string) $actor->id;
            $actorName = $actor->name;
            $actorRole = $actor->role instanceof \BackedEnum ? (string) $actor->role->value : (string) $actor->role;
        } elseif ($actor instanceof Registration) {
            $actorType = 'participant';
            $actorId = $actor->id;
            $actorName = $actor->profile?->full_name ?? 'Participant';
            $actorRole = 'officer';
        } elseif (is_array($actor)) {
            $actorType = (string) ($actor['type'] ?? 'system');
            $actorId = isset($actor['id']) ? (string) $actor['id'] : null;
            $actorName = (string) ($actor['name'] ?? 'System');
            $actorRole = isset($actor['role']) ? (string) $actor['role'] : null;
        }

        return AuditEvent::create([
            'id' => 'aud_'.Str::lower((string) Str::ulid()),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'actor_role' => $actorRole,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'summary' => $summary,
            'metadata' => $this->redact($metadata),
            'ip' => $request?->ip(),
            'occurred_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function redact(array $metadata): array
    {
        $blocked = config('npis.audit.redact_keys', []);
        $result = [];

        foreach ($metadata as $key => $value) {
            if (in_array((string) $key, $blocked, true) || str_contains(strtolower((string) $key), 'password')) {
                $result[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->redact($value);

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
