<?php

namespace App\Http\Controllers\Api\V1\ManifestChecker;

use App\Http\Requests\Emf\ExportRequest;
use App\Models\Emf\AuditEvent;
use App\Services\Emf\EmfExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends EmfApiController
{
    public function __construct(private readonly EmfExportService $exports) {}

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->integer('page', 1));
        $pageSize = min(100, max(1, (int) $request->integer('pageSize', 25)));
        $from = match ($request->query('period', '24h')) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $query = AuditEvent::query()
            ->when($request->query('action'), fn (Builder $q, string $action) => $q->where('action', 'like', $action.'%'))
            ->when($request->query('result'), fn (Builder $q, string $result) => $q->where('result', $result))
            ->when($request->query('search'), function (Builder $q, string $search): void {
                $like = '%'.$search.'%';
                $q->where(fn (Builder $inner) => $inner->where('actor_name', 'like', $like)->orWhere('detail', 'like', $like)->orWhere('target_id', 'like', $like));
            })
            ->where('occurred_at', '>=', $from);

        $total = (clone $query)->count();

        return $this->success([
            'data' => $query->orderByDesc('occurred_at')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
                ->map(fn (AuditEvent $event): array => [
                    'auditId' => $event->id,
                    'time' => $event->occurred_at->toIso8601String(),
                    'actor' => $event->actor_name,
                    'actorId' => $event->actor_id,
                    'action' => $event->action,
                    'target' => $event->target_type,
                    'targetId' => $event->target_id,
                    'detail' => $event->detail,
                    'result' => $event->result,
                    'ip' => $event->ip,
                ])->all(),
            'pagination' => $this->pagination($page, $pageSize, $total),
        ], 'Audit log.');
    }

    public function export(ExportRequest $request): JsonResponse
    {
        return $this->success($this->exports->create(
            'audit',
            (array) $request->input('filters', []),
            $this->officer($request),
            $request,
            (bool) $request->boolean('watermark', true),
            (bool) $request->boolean('requires2fa', true),
            $request->input('code'),
        ), 'Audit export ready.');
    }
}
