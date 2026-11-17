<?php

namespace App\Services\Npis;

use App\Exceptions\NpisApiException;
use App\Jobs\Npis\GenerateExportJob;
use App\Models\Npis\AdminUser;
use App\Models\Npis\ExportJob;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ExportService
{
    public function __construct(
        private readonly RegistrationSearchService $search,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    public function create(AdminUser $admin, array $filters, ?array $fields): array
    {
        $allowedFilters = [
            'q', 'status', 'participantType', 'sector', 'vendorService', 'protocolCategory', 'photo',
            'reviewFlag', 'stateCode', 'commandId', 'unitId', 'travelMode', 'arrivalDate', 'arrivalWindow',
            'departureDate', 'departureWindow', 'planStatus', 'airportCode', 'postSummitPlan',
            'accommodationPreference', 'allocationStatus', 'accommodationAllowance', 'stayNight',
            'track', 'subsector', 'sessionId', 'assigned', 'checkedIn', 'checkedInDate', 'certificate',
            'deliveryStatus', 'submittedFrom', 'submittedTo',
        ];

        $unknownFilters = array_values(array_diff(array_keys($filters), $allowedFilters));

        if ($unknownFilters !== []) {
            throw NpisApiException::validation('Unknown report filters.', ['filters' => implode(', ', $unknownFilters)]);
        }

        $available = collect(config('npis.exports.fields', []));
        $selected = $fields ?: $available
            ->filter(fn (array $field): bool => ! isset($field['permission']) || $admin->hasPermission($field['permission']))
            ->pluck('key')
            ->all();

        $unknown = array_values(array_diff($selected, $available->pluck('key')->all()));
        if ($unknown !== []) {
            throw NpisApiException::validation('Unknown export fields.', ['fields' => implode(', ', $unknown)]);
        }

        $disallowed = array_values(array_filter($selected, function (string $key) use ($available, $admin): bool {
            $permission = $available->firstWhere('key', $key)['permission'] ?? null;

            return $permission !== null && ! $admin->hasPermission($permission);
        }));

        if ($disallowed !== []) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'Your role cannot export some of the selected fields.', [
                'disallowedFields' => $disallowed,
            ]);
        }

        $registrations = $this->search->build($filters, $admin)->get();
        $count = $registrations->count();

        if ($count > (int) config('npis.exports.max_rows', 50000)) {
            throw NpisApiException::make(422, 'VALIDATION_FAILED', 'This export is too large. Narrow the filters and try again.');
        }

        $export = ExportJob::create([
            'id' => 'exp_'.Str::lower((string) Str::ulid()),
            'admin_user_id' => $admin->id,
            'filters' => $filters,
            'fields' => $selected,
            'status' => 'queued',
            'row_count' => $count,
            'expires_at' => now()->addSeconds((int) config('npis.exports.url_ttl', 900)),
        ]);

        GenerateExportJob::dispatch($export->id)->afterCommit();

        $this->audit->record(
            $admin,
            'export.created',
            'export',
            $export->id,
            "Exported {$count} registration(s) to CSV",
            ['filters' => $filters, 'fields' => $selected, 'rowCount' => $count],
            request(),
        );

        return $this->contract($export->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function show(ExportJob $export, AdminUser $admin): array
    {
        if ((int) $export->admin_user_id !== (int) $admin->id && $admin->role?->value !== 'super_admin') {
            throw NpisApiException::make(403, 'FORBIDDEN', 'This export belongs to another organizer.');
        }

        return $this->contract($export);
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(ExportJob $export): array
    {
        $downloadUrl = null;

        if ($export->status === 'ready' && $export->file_url) {
            $downloadUrl = URL::temporarySignedRoute(
                'npis.media.export',
                now()->addSeconds((int) config('npis.exports.url_ttl', 900)),
                ['export' => $export->id],
            );
        }

        return [
            'exportId' => $export->id,
            'status' => $export->status,
            'format' => 'csv',
            'rowCount' => $export->row_count,
            'filters' => $export->filters,
            'fields' => $export->fields,
            'downloadUrl' => $downloadUrl,
            'expiresAt' => $export->status === 'ready'
                ? now()->addSeconds((int) config('npis.exports.url_ttl', 900))->toIso8601String()
                : $export->expires_at?->toIso8601String(),
            'createdAt' => $export->created_at?->toIso8601String(),
            'error' => $export->error,
        ];
    }
}
