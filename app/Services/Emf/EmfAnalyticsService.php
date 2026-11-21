<?php

namespace App\Services\Emf;

use App\Models\Emf\Alert;
use App\Models\Emf\DutyPoint;
use App\Models\Emf\Manifest;
use App\Models\Emf\Officer;
use App\Models\Emf\ReportSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmfAnalyticsService
{
    public function __construct(
        private readonly EmfExportService $exports,
        private readonly EmfAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters, Officer $officer): array
    {
        $from = $this->periodStart((string) ($filters['period'] ?? '7d'));
        $state = $filters['stateCode'] ?? $officer->state_code;
        $manifests = Manifest::query()->where('state_code', $state)->where('checked_at', '>=', $from)
            ->when($filters['mode'] ?? null, fn (Builder $q, string $mode) => $q->where('mode', $mode));
        $alerts = Alert::query()->where('state_code', $state)->where('detected_at', '>=', $from);

        $checks = (clone $manifests)->count();
        $hits = (clone $alerts)->whereIn('type', ['critical', 'flagged'])->count();
        $responded = (clone $alerts)->whereNotNull('acknowledged_at')->count();
        $avgResponse = (int) round((clone $alerts)->whereNotNull('acknowledged_at')->get()
            ->avg(fn (Alert $a): int => (int) $a->detected_at->diffInSeconds($a->acknowledged_at)) ?? 0);

        $checksPerDay = collect(range(6, 0))->map(function (int $daysAgo) use ($state, $filters): array {
            $date = now()->subDays($daysAgo)->toDateString();

            return [
                'date' => $date,
                'checks' => Manifest::query()->where('state_code', $state)->whereDate('checked_at', $date)
                    ->when($filters['mode'] ?? null, fn (Builder $q, string $mode) => $q->where('mode', $mode))->count(),
                'hits' => Alert::query()->where('state_code', $state)->whereDate('detected_at', $date)->whereIn('type', ['critical', 'flagged'])->count(),
            ];
        })->values()->all();

        return [
            'period' => $filters['period'] ?? '7d',
            'metrics' => [
                'checks' => $checks,
                'hits' => $hits,
                'hitRate' => $checks > 0 ? round(($hits / $checks) * 100, 1) : 0,
                'avgResponseSeconds' => $avgResponse,
                'avgResponseLabel' => $this->duration($avgResponse),
                'acknowledged' => $responded,
                'ackRate' => $hits > 0 ? round(($responded / $hits) * 100, 1) : 0,
            ],
            'checksPerDay' => $checksPerDay,
            'coverage' => $this->coverageGaps($state),
            'leaderboard' => $this->leaderboard($state, $from),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function unit(DutyPoint $point, array $filters): array
    {
        $from = $this->periodStart((string) ($filters['period'] ?? '7d'));
        $manifests = Manifest::query()->where('duty_point_id', $point->id)->where('checked_at', '>=', $from);
        $alerts = Alert::query()->where('duty_point_id', $point->id)->where('detected_at', '>=', $from);
        $checks = (clone $manifests)->count();
        $hits = (clone $alerts)->whereIn('type', ['critical', 'flagged'])->count();
        $acknowledged = (clone $alerts)->whereNotNull('acknowledged_at')->get();
        $avgResponse = (int) round($acknowledged->avg(fn (Alert $a): int => (int) $a->detected_at->diffInSeconds($a->acknowledged_at)) ?? 0);

        return [
            'dutyPoint' => [
                'dutyPointId' => $point->id,
                'name' => $point->name,
                'type' => $point->type,
                'stateCode' => $point->state_code,
                'lgaName' => $point->lga?->name,
            ],
            'metrics' => [
                'checks' => $checks,
                'hits' => $hits,
                'avgResponseSeconds' => $avgResponse,
                'avgResponseLabel' => $this->duration($avgResponse),
                'ackRate' => $hits > 0 ? round(($acknowledged->count() / $hits) * 100, 1) : 0,
            ],
            'checksPerDay' => collect(range(6, 0))->map(fn (int $daysAgo): array => [
                'date' => now()->subDays($daysAgo)->toDateString(),
                'checks' => Manifest::query()->where('duty_point_id', $point->id)->whereDate('checked_at', now()->subDays($daysAgo)->toDateString())->count(),
            ])->values()->all(),
            'verdictMix' => collect(['clear', 'flagged', 'mismatch', 'unverified'])->map(fn (string $verdict): array => [
                'key' => $verdict,
                'label' => ucfirst($verdict),
                'count' => Manifest::query()->where('duty_point_id', $point->id)->where('verdict', $verdict)->count(),
            ])->values()->all(),
            'officers' => Officer::query()->where('duty_point_id', $point->id)->get()->map(fn (Officer $officer): array => [
                'officerId' => $officer->id,
                'fullName' => $officer->full_name,
                'checks' => Manifest::query()->where('checked_by_officer_id', $officer->id)->where('checked_at', '>=', $from)->count(),
                'hits' => Alert::query()->where('assigned_officer_id', $officer->id)->where('detected_at', '>=', $from)->whereIn('type', ['critical', 'flagged'])->count(),
            ])->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function coverageGaps(string $stateCode): array
    {
        return DutyPoint::query()->where('state_code', $stateCode)
            ->withCount(['roster as conflict_count' => fn (Builder $q) => $q->where('status', 'conflict')])
            ->get()->filter(fn (DutyPoint $point): bool => $point->officers_count === 0 || $point->conflict_count > 0)
            ->map(fn (DutyPoint $point): array => [
                'dutyPointId' => $point->id,
                'name' => $point->name,
                'type' => $point->type,
                'reason' => $point->conflict_count > 0 ? 'Roster conflict' : 'No officer on duty',
                'officers' => $point->officers_count,
                'conflicts' => $point->conflict_count,
            ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function export(array $filters, Officer $officer, Request $request, ?string $code, bool $watermark): array
    {
        return $this->exports->create('manifests', $filters, $officer, $request, $watermark, true, $code);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function schedules(): array
    {
        return ReportSchedule::query()->orderByDesc('created_at')->get()->map(fn (ReportSchedule $schedule): array => [
            'scheduleId' => $schedule->id,
            'name' => $schedule->name,
            'reportType' => $schedule->report_type,
            'frequency' => $schedule->frequency,
            'recipients' => $schedule->recipients,
            'nextRunAt' => $schedule->next_run_at->toIso8601String(),
            'active' => $schedule->active,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSchedule(array $payload, Officer $officer, Request $request): ReportSchedule
    {
        $schedule = ReportSchedule::create([
            'id' => 'sch_'.Str::lower((string) Str::ulid()),
            'name' => $payload['name'],
            'report_type' => $payload['reportType'],
            'filters' => $payload['filters'] ?? [],
            'frequency' => $payload['frequency'],
            'recipients' => $payload['recipients'],
            'next_run_at' => $payload['nextRunAt'] ?? now()->addWeek(),
            'active' => true,
            'created_by' => $officer->id,
        ]);

        $this->audit->record($officer, 'report.schedule_created', 'report_schedule', $schedule->id, 'Scheduled '.$schedule->name, 'done', [], $request);

        return $schedule;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function leaderboard(string $stateCode, Carbon $from): array
    {
        return DutyPoint::query()->where('state_code', $stateCode)->get()->map(function (DutyPoint $point) use ($from): array {
            $checks = Manifest::query()->where('duty_point_id', $point->id)->where('checked_at', '>=', $from)->count();
            $hits = Alert::query()->where('duty_point_id', $point->id)->where('detected_at', '>=', $from)->whereIn('type', ['critical', 'flagged'])->count();

            return [
                'dutyPointId' => $point->id,
                'name' => $point->name,
                'checks' => $checks,
                'hits' => $hits,
                'hitRate' => $checks > 0 ? round(($hits / $checks) * 100, 1) : 0,
            ];
        })->sortByDesc('checks')->values()->all();
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '30d' => now()->subDays(30),
            'quarter' => now()->subQuarter(),
            default => now()->subDays(7),
        };
    }

    private function duration(int $seconds): string
    {
        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }
}
