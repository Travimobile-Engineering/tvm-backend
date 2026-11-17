<?php

namespace App\Services\Npis;

use App\Exceptions\NpisApiException;
use App\Jobs\Npis\GenerateCertificatePdfJob;
use App\Jobs\Npis\SendCertificateDeliveryJob;
use App\Models\Npis\AdminUser;
use App\Models\Npis\Certificate;
use App\Models\Npis\CertificateDelivery;
use App\Models\Npis\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CertificateService
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly CertificatePdfService $pdfs,
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  list<string>  $registrationIds
     * @return array<string, mixed>
     */
    public function issue(AdminUser $admin, array $registrationIds): array
    {
        $results = [];

        DB::transaction(function () use ($admin, $registrationIds, &$results): void {
            foreach ($registrationIds as $registrationId) {
                $registration = Registration::query()
                    ->with(['contact', 'profile', 'checkIns', 'certificate'])
                    ->find($registrationId);

                if (! $registration) {
                    $results[] = ['registrationId' => $registrationId, 'outcome' => 'not_found', 'certificate' => null];

                    continue;
                }

                if ($registration->certificate) {
                    $results[] = [
                        'registrationId' => $registrationId,
                        'outcome' => 'already_issued',
                        'certificate' => $this->contract($registration->certificate),
                    ];

                    continue;
                }

                if ($this->registrations->certificateEligibility($registration) !== 'eligible') {
                    $results[] = ['registrationId' => $registrationId, 'outcome' => 'not_eligible', 'certificate' => null];

                    continue;
                }

                $certificate = Certificate::create([
                    'id' => 'cer_'.Str::lower((string) Str::ulid()),
                    'registration_id' => $registration->id,
                    'certificate_number' => $this->nextNumber(),
                    'issued_at' => now(),
                    'issued_by_admin_id' => $admin->id,
                    'delivery_channel' => $registration->contact->channel,
                    'delivery_status' => 'queued',
                    'delivery_attempts' => 0,
                ]);

                GenerateCertificatePdfJob::dispatch($certificate->id)->afterCommit();

                $results[] = [
                    'registrationId' => $registrationId,
                    'outcome' => 'issued',
                    'certificate' => $this->contract($certificate),
                ];
            }

            $issued = count(array_filter($results, fn (array $result): bool => $result['outcome'] === 'issued'));

            if ($issued > 0) {
                $this->audit->record(
                    $admin,
                    'certificate.issued',
                    'certificate',
                    'batch',
                    "Issued {$issued} certificate(s)",
                    [
                        'registrationIds' => array_values(array_map(
                            fn (array $result): string => $result['registrationId'],
                            array_filter($results, fn (array $result): bool => $result['outcome'] === 'issued')
                        )),
                    ],
                    request(),
                );
            }
        });

        return ['results' => $results];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(?string $deliveryStatus): array
    {
        return Certificate::query()
            ->when($deliveryStatus, fn ($query) => $query->where('delivery_status', $deliveryStatus))
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $certificate): array => $this->contract($certificate))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function retryDelivery(AdminUser $admin, Certificate $certificate): array
    {
        if (in_array($certificate->delivery_status, ['queued', 'sent'], true)) {
            throw NpisApiException::make(409, 'VALIDATION_FAILED', 'A delivery attempt is already in progress. Wait for it to finish before retrying.');
        }

        $certificate->forceFill(['delivery_status' => 'queued', 'last_error' => null])->save();

        CertificateDelivery::create([
            'certificate_id' => $certificate->id,
            'channel' => $certificate->delivery_channel,
            'status' => 'queued',
            'attempted_at' => now(),
        ]);

        SendCertificateDeliveryJob::dispatch($certificate->id)->afterCommit();

        $this->audit->record(
            $admin,
            'certificate.delivery_retried',
            'certificate',
            $certificate->id,
            'Retried delivery of '.$certificate->certificate_number,
            [],
            request(),
        );

        return $this->contract($certificate->fresh());
    }

    public function deliver(string $certificateId): void
    {
        $certificate = Certificate::query()->with('registration.contact')->find($certificateId);

        if (! $certificate) {
            return;
        }

        $registration = $certificate->registration;
        $contact = $registration?->contact;
        $recipient = $contact ? (string) $contact->destination : null;
        $channel = $contact ? $contact->channel : $certificate->delivery_channel;

        try {
            $this->pdfs->ensureGenerated($certificate);

            if (! $recipient) {
                throw new RuntimeException('No verified destination is available for certificate delivery.');
            }

            $certificate->forceFill([
                'delivery_channel' => $channel,
                'delivery_status' => 'sent',
                'delivery_attempts' => $certificate->delivery_attempts + 1,
                'last_attempt_at' => now(),
                'last_error' => null,
            ])->save();

            $notification = $this->notifications->queue(
                'certificate_delivery',
                $channel,
                $recipient,
                ['certificateNumber' => $certificate->certificate_number],
                'certificate',
                $certificate->id,
            );

            CertificateDelivery::create([
                'certificate_id' => $certificate->id,
                'channel' => $channel,
                'status' => 'sent',
                'attempted_at' => now(),
                'provider_message_id' => $notification?->id,
            ]);
        } catch (Throwable $exception) {
            $certificate->forceFill([
                'delivery_status' => 'failed',
                'delivery_attempts' => $certificate->delivery_attempts + 1,
                'last_attempt_at' => now(),
                'last_error' => $exception->getMessage(),
            ])->save();

            CertificateDelivery::create([
                'certificate_id' => $certificate->id,
                'channel' => $channel,
                'status' => 'failed',
                'attempted_at' => now(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function participantCertificate(Registration $registration): array
    {
        $certificate = Certificate::query()->where('registration_id', $registration->id)->first();

        if (! $certificate) {
            throw NpisApiException::make(
                404,
                'NOT_FOUND',
                'Your certificate has not been issued yet. Certificates are issued after the summit to delegates who meet the attendance requirement.'
            );
        }

        $this->pdfs->ensureGenerated($certificate);

        return [
            'certificateNumber' => $certificate->certificate_number,
            'downloadUrl' => URL::temporarySignedRoute(
                'npis.media.certificate',
                now()->addSeconds((int) config('npis.certificate.download_url_ttl', 900)),
                ['certificate' => $certificate->id],
            ),
            'expiresAt' => now()->addSeconds((int) config('npis.certificate.download_url_ttl', 900))->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(Certificate $certificate): array
    {
        $issuer = $certificate->issued_by_admin_id
            ? AdminUser::query()->whereKey($certificate->issued_by_admin_id)->first()
            : null;

        return [
            'certificateId' => $certificate->id,
            'certificateNumber' => $certificate->certificate_number,
            'registrationId' => $certificate->registration_id,
            'summitTagNumber' => $certificate->registration
                ? $certificate->registration->summit_tag_number
                : Registration::query()->find($certificate->registration_id)?->summit_tag_number,
            'issuedAt' => $certificate->issued_at->toIso8601String(),
            'issuedBy' => $issuer?->name ?: 'Organizer',
            'delivery' => [
                'channel' => $certificate->delivery_channel,
                'status' => $certificate->delivery_status,
                'attempts' => $certificate->delivery_attempts,
                'lastAttemptAt' => $certificate->last_attempt_at?->toIso8601String(),
                'lastError' => $certificate->last_error,
            ],
        ];
    }

    private function nextNumber(): string
    {
        $sequence = Certificate::query()->count() + 1;

        do {
            $number = 'NPIS26-CERT-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $exists = Certificate::query()->where('certificate_number', $number)->exists();
            $sequence++;
        } while ($exists);

        return $number;
    }
}
