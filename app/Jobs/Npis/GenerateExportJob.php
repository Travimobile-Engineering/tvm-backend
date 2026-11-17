<?php

namespace App\Jobs\Npis;

use App\Contracts\NpisCloudStorage;
use App\Models\Npis\AdminUser;
use App\Models\Npis\ExportJob;
use App\Models\Npis\Itinerary;
use App\Models\Npis\Registration;
use App\Services\Npis\RegistrationSearchService;
use App\Services\Npis\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $exportId) {}

    public function handle(RegistrationSearchService $search, ReportService $reports, NpisCloudStorage $cloud): void
    {
        $export = ExportJob::find($this->exportId);

        if (! $export) {
            return;
        }

        $export->forceFill(['status' => 'processing'])->save();

        try {
            $admin = AdminUser::find($export->admin_user_id);

            if (! $admin) {
                throw new RuntimeException('The organizer who created this export no longer exists.');
            }

            $registrations = $search->build($export->filters ?? [], $admin)
                ->with(['profile', 'contact', 'itinerary', 'accommodation.allocation', 'badge', 'interests', 'assignments.session', 'checkIns', 'certificate'])
                ->get();

            $fields = $export->fields;
            /** @var list<array{key: string, label: string, permission?: string}> $configFields */
            $configFields = config('npis.exports.fields', []);
            $labels = collect($configFields)->keyBy('key');
            $header = array_map(fn (string $key): string => $labels[$key]['label'] ?? $key, $fields);

            $lines = [$this->csvRow($header)];

            foreach ($registrations as $registration) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = $this->value($registration, $field);
                }
                $lines[] = $this->csvRow($row);
            }

            $csv = implode("\r\n", $lines);
            $upload = $cloud->uploadContents(
                $csv,
                $export->id.'.csv',
                config('npis.storage.folders.exports', 'npis/exports'),
            );

            $export->forceFill([
                'status' => 'ready',
                'row_count' => $registrations->count(),
                'file_path' => null,
                'file_url' => $upload['url'],
                'file_public_id' => $upload['public_id'],
                'error' => null,
                'expires_at' => now()->addSeconds((int) config('npis.exports.url_ttl', 900)),
            ])->save();
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  list<string>  $values
     */
    private function csvRow(array $values): string
    {
        return implode(',', array_map($this->escapeCell(...), $values));
    }

    private function escapeCell(string $value): string
    {
        if (preg_match('/^[=+\-@]/', $value)) {
            $value = "'".$value;
        }

        return '"'.str_replace('"', '""', $value).'"';
    }

    private function value(Registration $registration, string $field): string
    {
        $profile = $registration->profile;
        $itinerary = $registration->itinerary;
        $accommodation = $registration->accommodation;

        return match ($field) {
            'summitTagNumber' => (string) $registration->summit_tag_number,
            'participantType' => $registration->participant_type->value,
            'fullName' => (string) $profile?->full_name,
            'sector' => (string) $profile?->sector,
            'organization' => (string) $profile?->organization,
            'designation' => (string) $profile?->designation,
            'vendorService' => (string) ($profile?->vendor_service_other ?: $profile?->vendor_service),
            'protocolCategory' => (string) ($registration->badge ? $registration->badge->category : ''),
            'photo' => $registration->badge && $registration->badge->photo_url ? 'Yes' : 'No',
            'rank' => (string) ($profile?->rank ? $profile->rank->name : $profile?->rank_code),
            'state' => (string) ($profile?->state ? $profile->state->name : $profile?->state_code),
            'command' => (string) ($profile?->command ? $profile->command->name : $profile?->command_id),
            'unit' => (string) ($profile?->unit ? $profile->unit->name : $profile?->unit_other_name),
            'status' => $registration->status->value,
            'phone' => (string) ($registration->contact && $registration->contact->channel === 'sms' ? $registration->contact->destination : ''),
            'email' => (string) ($registration->contact && $registration->contact->channel === 'email' ? $registration->contact->destination : ''),
            'travelMode' => (string) $itinerary?->travel_mode,
            'arrival' => $itinerary ? $itinerary->arrival_date?->format('Y-m-d').' '.($itinerary->arrival_time ?? $itinerary->arrival_time_window ?? '') : '',
            'departure' => $itinerary ? $itinerary->departure_date?->format('Y-m-d').' '.($itinerary->departure_time ?? $itinerary->departure_time_window ?? '') : '',
            'planStatus' => $this->planStatus($itinerary),
            'airport' => (string) $itinerary?->departure_airport_code,
            'accommodation' => (string) $accommodation?->preference,
            'checkIn' => $accommodation?->check_in_date?->format('Y-m-d') ?? '',
            'checkOut' => $accommodation?->check_out_date?->format('Y-m-d') ?? '',
            'allowance' => $accommodation?->allowance_requested ? 'Yes' : 'No',
            'payoutBank' => (string) $accommodation?->payout_bank_code,
            'payoutAccountNumber' => (string) $accommodation?->payout_account_number,
            'payoutAccountName' => (string) $accommodation?->payout_account_name,
            'track' => (string) $registration->interests?->track,
            'subsectors' => implode('; ', $registration->interests ? $registration->interests->transport_subsectors : []),
            'sessions' => $registration->assignments->pluck('session_id')->implode('; '),
            'attendance' => (string) $registration->checkIns->pluck('event_date')->unique()->count(),
            default => '',
        };
    }

    private function planStatus(?Itinerary $itinerary): string
    {
        if (! $itinerary instanceof Itinerary) {
            return '';
        }

        if ($itinerary->travel_mode === 'undecided'
            || $itinerary->arrival_plan_status === 'undecided'
            || $itinerary->departure_plan_status === 'undecided') {
            return 'undecided';
        }

        return $itinerary->arrival_plan_status === 'confirmed' && $itinerary->departure_plan_status === 'confirmed'
            ? 'confirmed'
            : 'preferred';
    }
}
