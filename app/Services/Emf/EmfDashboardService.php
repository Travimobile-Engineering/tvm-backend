<?php

namespace App\Services\Emf;

use App\Models\Emf\Alert;
use App\Models\Emf\CaseFile;
use App\Models\Emf\DutyPoint;
use App\Models\Emf\Incident;
use App\Models\Emf\Manifest;
use App\Models\Emf\Officer;
use App\Models\Emf\WatchlistEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EmfDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(Officer $officer, array $filters): array
    {
        $from = $this->periodStart((string) ($filters['period'] ?? '24h'));
        $state = $filters['stateCode'] ?? $officer->state_code;
        $lga = $filters['lgaId'] ?? null;
        $mode = $filters['mode'] ?? null;

        $manifests = Manifest::query()
            ->where('state_code', $state)
            ->when($lga, fn (Builder $q) => $q->where('lga_id', $lga))
            ->when($mode, fn (Builder $q) => $q->where('mode', $mode));

        $alerts = Alert::query()
            ->where('state_code', $state)
            ->when($lga, fn (Builder $q) => $q->where('lga_id', $lga))
            ->when($mode, fn (Builder $q) => $q->where('mode', $mode))
            ->where('detected_at', '>=', $from);

        $incidents = Incident::query()
            ->where('state_code', $state)
            ->when($lga, fn (Builder $q) => $q->where('lga_id', $lga))
            ->where('reported_at', '>=', $from);

        $acknowledged = (clone $alerts)->whereNotNull('acknowledged_at')->get();
        $avgAck = (int) round($acknowledged->avg(fn (Alert $alert): int => (int) $alert->detected_at->diffInSeconds($alert->acknowledged_at)) ?? 0);

        $dutyPoints = DutyPoint::query()
            ->where('state_code', $state)
            ->when($lga, fn (Builder $q) => $q->where('lga_id', $lga));

        $coverageGaps = (clone $dutyPoints)
            ->withCount(['roster as conflict_count' => fn (Builder $q) => $q->where('status', 'conflict')])
            ->get()
            ->filter(fn (DutyPoint $point): bool => $point->officers_count === 0 || $point->conflict_count > 0)
            ->map(fn (DutyPoint $point): array => [
                'dutyPointId' => $point->id,
                'name' => $point->name,
                'type' => $point->type,
                'reason' => $point->conflict_count > 0 ? 'Roster conflict' : 'No officer on duty',
                'status' => $point->conflict_count > 0 ? 'conflict' : 'critical',
                'officers' => $point->officers_count,
                'conflicts' => $point->conflict_count,
            ])->values()->all();

        return [
            'scope' => [
                'stateCode' => $state,
                'lgaId' => $lga,
                'mode' => $mode,
                'period' => $filters['period'] ?? '24h',
                'liveAt' => now()->toIso8601String(),
            ],
            'metrics' => [
                'activeManifests' => (clone $manifests)->count(),
                'watchlistHits' => (clone $alerts)->whereIn('type', ['critical', 'flagged'])->count(),
                'openIncidents' => (clone $incidents)->where('status', '!=', 'resolved')->count(),
                'officersOnDuty' => Officer::query()->where('state_code', $state)->where('status', 'on_duty')->count(),
                'avgAckSeconds' => $avgAck,
                'avgAckLabel' => $this->duration($avgAck),
            ],
            'liveAlerts' => (clone $alerts)->with(['manifest', 'watchlistEntry', 'assignee'])
                ->whereIn('status', ['open', 'acknowledged', 'assigned'])
                ->orderByDesc('detected_at')->limit(6)->get()
                ->map(fn (Alert $alert): array => $this->alertSummary($alert))->all(),
            'manifestsByMode' => $this->modeBreakdown(clone $manifests, $from),
            'incidentsBySeverity' => $this->severityBreakdown(clone $incidents),
            'coverageGaps' => $coverageGaps,
            'latestWatchlistHits' => (clone $alerts)->with(['manifest', 'watchlistEntry'])
                ->whereNotNull('watchlist_entry_id')->orderByDesc('detected_at')->limit(5)->get()
                ->map(fn (Alert $alert): array => $this->alertSummary($alert))->all(),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function search(string $term, Officer $officer): array
    {
        $term = trim($term);
        $like = '%'.$term.'%';

        $manifests = Manifest::query()
            ->where('state_code', $officer->state_code)
            ->where(function (Builder $q) use ($like): void {
                $q->where('manifest_number', 'like', $like)->orWhere('vehicle_registration', 'like', $like)
                    ->orWhereHas('passengers', fn (Builder $p) => $p->where('full_name', 'like', $like)->orWhere('nin', 'like', $like));
            })->limit(5)->get()->map(fn (Manifest $m): array => [
                'type' => 'manifest',
                'id' => $m->id,
                'title' => $m->manifest_number,
                'subtitle' => $m->operator.' · '.$m->route_origin.' → '.$m->route_destination,
                'verdict' => $m->verdict,
            ])->all();

        $watchlist = WatchlistEntry::query()
            ->where(fn (Builder $q) => $q->where('full_name', 'like', $like)->orWhere('nin', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('plate_number', 'like', $like))
            ->limit(5)->get()->map(fn (WatchlistEntry $entry): array => [
                'type' => 'watchlist',
                'id' => $entry->id,
                'title' => $entry->full_name ?? $entry->entry_number,
                'subtitle' => $entry->entry_number.' · '.ucfirst($entry->risk),
                'verdict' => $entry->risk,
            ])->all();

        $incidents = Incident::query()->where('state_code', $officer->state_code)
            ->where(fn (Builder $q) => $q->where('incident_number', 'like', $like)->orWhere('title', 'like', $like))
            ->limit(5)->get()->map(fn (Incident $i): array => [
                'type' => 'incident', 'id' => $i->id, 'title' => $i->incident_number,
                'subtitle' => $i->title, 'verdict' => $i->severity,
            ])->all();

        $cases = CaseFile::query()->where(fn (Builder $q) => $q->where('case_number', 'like', $like)->orWhere('title', 'like', $like))
            ->limit(5)->get()->map(fn (CaseFile $c): array => [
                'type' => 'case', 'id' => $c->id, 'title' => $c->case_number,
                'subtitle' => $c->title, 'verdict' => $c->status,
            ])->all();

        $officers = Officer::query()->where('state_code', $officer->state_code)
            ->where(fn (Builder $q) => $q->where('full_name', 'like', $like)->orWhere('service_number', 'like', $like))
            ->limit(5)->get()->map(fn (Officer $o): array => [
                'type' => 'officer', 'id' => $o->id, 'title' => $o->full_name,
                'subtitle' => $o->service_number.' · '.$o->rank, 'verdict' => $o->status,
            ])->all();

        return ['manifests' => $manifests, 'watchlist' => $watchlist, 'incidents' => $incidents, 'cases' => $cases, 'officers' => $officers];
    }

    /**
     * @param  Builder<Manifest>  $query
     * @return list<array<string, mixed>>
     */
    private function modeBreakdown(Builder $query, Carbon $from): array
    {
        /** @var array<string, string> $modes */
        $modes = (array) config('emf.modes', []);

        return collect($modes)->map(function (string $label, string $mode) use ($query, $from): array {
            $count = (clone $query)->where('mode', $mode)->where('checked_at', '>=', $from)->count();

            return ['key' => $mode, 'label' => $label, 'count' => $count];
        })->values()->all();
    }

    /**
     * @param  Builder<Incident>  $query
     * @return list<array<string, mixed>>
     */
    private function severityBreakdown(Builder $query): array
    {
        /** @var list<string> $severities */
        $severities = (array) config('emf.severities', []);

        return collect($severities)->map(fn (string $severity): array => [
            'key' => $severity,
            'label' => ucfirst($severity),
            'count' => (clone $query)->where('severity', $severity)->where('status', '!=', 'resolved')->count(),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function alertSummary(Alert $alert): array
    {
        $subject = $alert->watchlistEntry;
        $slaRemaining = max(0, $alert->sla_seconds - (int) $alert->detected_at->diffInSeconds($alert->acknowledged_at ?? now()));

        return [
            'alertId' => $alert->id,
            'alertNumber' => $alert->alert_number,
            'type' => $alert->type,
            'title' => $alert->title,
            'subjectName' => $subject->full_name ?? $alert->summary,
            'confidence' => $alert->confidence,
            'mode' => $alert->mode,
            'route' => $alert->route,
            'manifest' => $alert->manifest ? [
                'manifestId' => $alert->manifest->id,
                'manifestNumber' => $alert->manifest->manifest_number,
            ] : null,
            'status' => $alert->status,
            'detectedAt' => $alert->detected_at->toIso8601String(),
            'slaRemainingSeconds' => $slaRemaining,
            'slaLabel' => $alert->status === 'open' ? 'SLA '.$this->clock($slaRemaining) : null,
            'responder' => $alert->assignee->full_name ?? null,
            'responderNotAcknowledged' => $alert->status === 'open',
        ];
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            'quarter' => now()->subQuarter(),
            default => now()->subDay(),
        };
    }

    private function duration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '—';
        }

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }

    private function clock(int $seconds): string
    {
        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
