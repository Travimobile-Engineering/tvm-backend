<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\NpisApiController;
use App\Models\Npis\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends NpisApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'action' => ['nullable', 'string', 'max:64'],
        ]);

        $page = max(1, (int) $request->query('page', 1));
        $pageSize = min(100, max(1, (int) $request->query('pageSize', 25)));
        $action = $request->query('action');

        $query = AuditEvent::query()
            ->when($action, fn ($builder) => $builder->where('action', 'like', $action.'%'))
            ->orderByDesc('occurred_at');

        $total = (clone $query)->count();

        $items = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->get()->map(fn (AuditEvent $event): array => [
            'auditId' => $event->id,
            'occurredAt' => $event->occurred_at?->toIso8601String(),
            'actor' => [
                'userId' => $event->actor_id,
                'name' => $event->actor_name,
                'role' => $event->actor_role,
            ],
            'action' => $event->action,
            'targetType' => $event->target_type,
            'targetId' => $event->target_id,
            'summary' => $event->summary,
            'metadata' => $event->metadata ?? [],
        ])->all();

        return $this->success([
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ], 'Audit events retrieved successfully');
    }
}
