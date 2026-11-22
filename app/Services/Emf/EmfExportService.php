<?php

namespace App\Services\Emf;

use App\Contracts\NpisCloudStorage;
use App\Exceptions\NpisApiException;
use App\Models\Emf\AuditEvent;
use App\Models\Emf\CaseFile;
use App\Models\Emf\ExportJob;
use App\Models\Emf\Manifest;
use App\Models\Emf\Officer;
use App\Models\Emf\WatchlistEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmfExportService
{
    public function __construct(
        private readonly NpisCloudStorage $cloud,
        private readonly EmfAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function create(string $reportType, array $filters, Officer $officer, Request $request, bool $watermark = false, bool $requiresTwoFactor = false, ?string $code = null): array
    {
        if ($requiresTwoFactor && ! preg_match('/^\d{6}$/', (string) $code)) {
            throw NpisApiException::validation('Enter the 6-digit 2FA code to export this report.', ['code' => 'Must be 6 digits']);
        }

        $rows = $this->rows($reportType, $filters, $officer);
        $csv = $this->csv($rows, $watermark ? $officer : null);
        $export = ExportJob::create([
            'id' => 'exp_'.Str::lower((string) Str::ulid()),
            'requested_by' => $officer->id,
            'report_type' => $reportType,
            'filters' => $filters,
            'format' => 'csv',
            'status' => 'ready',
            'row_count' => max(0, count($rows) - 1),
            'watermark' => $watermark,
            'requires_2fa' => $requiresTwoFactor,
            'verified_at' => $requiresTwoFactor ? now() : null,
            'expires_at' => now()->addMinutes(15),
        ]);

        $upload = $this->cloud->uploadContents($csv, $export->id.'.csv', 'emf/exports');
        $export->forceFill(['file_url' => $upload['url']])->save();

        $this->audit->record($officer, 'export.created', 'export', $export->id, 'Exported '.$reportType.' report', 'done', ['rowCount' => $export->row_count], $request);

        return $this->contract($export->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(ExportJob $export): array
    {
        $downloadUrl = $export->file_url
            ? URL::temporarySignedRoute('emf.media.export', now()->addMinutes(15), ['export' => $export->id])
            : null;

        return [
            'exportId' => $export->id,
            'reportType' => $export->report_type,
            'status' => $export->status,
            'format' => $export->format,
            'rowCount' => $export->row_count,
            'watermark' => $export->watermark,
            'requires2fa' => $export->requires_2fa,
            'downloadUrl' => $downloadUrl,
            'expiresAt' => $export->expires_at?->toIso8601String(),
            'createdAt' => $export->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<int, string>>
     */
    private function rows(string $reportType, array $filters, Officer $officer): array
    {
        return match ($reportType) {
            'manifests' => $this->manifestRows($filters, $officer),
            'watchlist' => $this->watchlistRows($filters),
            'cases' => $this->caseRows($filters),
            'audit' => $this->auditRows($filters),
            default => [['Report', 'Value'], ['Report type', $reportType], ['Generated at', now()->toIso8601String()]],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<int, string>>
     */
    private function manifestRows(array $filters, Officer $officer): array
    {
        $rows = [['Manifest', 'Mode', 'Operator', 'Route', 'Passengers', 'Checked', 'Verdict', 'Checked at']];
        Manifest::query()->where('state_code', $officer->state_code)
            ->when($filters['manifestNumber'] ?? null, fn ($q, string $number) => $q->where('manifest_number', $number))
            ->when($filters['mode'] ?? null, fn ($q, string $mode) => $q->where('mode', $mode))
            ->when($filters['verdict'] ?? null, fn ($q, string $verdict) => $q->where('verdict', $verdict))
            ->orderByDesc('checked_at')->limit(5000)->get()
            ->each(function (Manifest $manifest) use (&$rows): void {
                $rows[] = [
                    $manifest->manifest_number, $manifest->mode, $manifest->operator,
                    $manifest->route_origin.' → '.$manifest->route_destination,
                    (string) $manifest->passenger_count, (string) $manifest->checked_count,
                    $manifest->verdict, (string) $manifest->checked_at?->toIso8601String(),
                ];
            });

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<int, string>>
     */
    private function watchlistRows(array $filters): array
    {
        $rows = [['Entry', 'Type', 'Risk', 'Name', 'NIN', 'Phone', 'Plate', 'Status', 'Expires']];
        WatchlistEntry::query()->when($filters['risk'] ?? null, fn ($q, string $risk) => $q->where('risk', $risk))
            ->orderByDesc('created_at')->limit(5000)->get()->each(function (WatchlistEntry $entry) use (&$rows): void {
                $rows[] = [
                    $entry->entry_number, $entry->type, $entry->risk, (string) $entry->full_name,
                    (string) $entry->nin, (string) $entry->phone, (string) $entry->plate_number,
                    $entry->status, (string) $entry->expires_at?->toIso8601String(),
                ];
            });

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<int, string>>
     */
    private function caseRows(array $filters): array
    {
        $rows = [['Case', 'Title', 'Status', 'Severity', 'Opened', 'Closed']];
        CaseFile::query()->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('status', $status))
            ->orderByDesc('opened_at')->limit(5000)->get()->each(function (CaseFile $case) use (&$rows): void {
                $rows[] = [$case->case_number, $case->title, $case->status, $case->severity, $case->opened_at->toIso8601String(), (string) $case->closed_at?->toIso8601String()];
            });

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<int, string>>
     */
    private function auditRows(array $filters): array
    {
        $rows = [['Time', 'Actor', 'Action', 'Target', 'Detail', 'Result']];
        AuditEvent::query()->when($filters['action'] ?? null, fn ($q, string $action) => $q->where('action', 'like', $action.'%'))
            ->when($filters['result'] ?? null, fn ($q, string $result) => $q->where('result', $result))
            ->orderByDesc('occurred_at')->limit(5000)->get()->each(function (AuditEvent $event) use (&$rows): void {
                $rows[] = [$event->occurred_at->toIso8601String(), $event->actor_name, $event->action, $event->target_type.' '.$event->target_id, (string) $event->detail, $event->result];
            });

        return $rows;
    }

    /**
     * @param  list<array<int, string>>  $rows
     */
    private function csv(array $rows, ?Officer $watermark): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map($this->escape(...), $row));
        }

        if ($watermark instanceof Officer) {
            $lines[] = '';
            $lines[] = 'Exported by '.$watermark->full_name.' ('.$watermark->service_number.') at '.now()->toIso8601String();
        }

        return implode("\r\n", $lines);
    }

    private function escape(string $value): string
    {
        if (preg_match('/^[=+\-@]/', $value)) {
            $value = "'".$value;
        }

        return '"'.str_replace('"', '""', $value).'"';
    }
}
