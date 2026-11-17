<?php

namespace App\Services\Npis;

use App\Contracts\NpisCloudStorage;
use App\Models\Npis\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificatePdfService
{
    public function __construct(private readonly NpisCloudStorage $cloud) {}

    public function generate(Certificate $certificate): string
    {
        $certificate->loadMissing(['registration.event', 'registration.profile.rank']);

        $registration = $certificate->registration;
        $event = $registration->event ?: app(EventService::class)->current();
        $profile = $registration->profile;
        $rank = $profile?->rank?->abbreviation;

        $pdf = Pdf::loadView('npis.certificate', [
            'event' => $event,
            'certificate' => $certificate,
            'registration' => $registration,
            'fullName' => $profile->full_name ?? 'Participant',
            'rank' => $rank,
            'organisation' => $profile->organization ?? ($profile->sector === 'police' ? 'Nigeria Police Force' : null),
            'signatures' => config('npis.certificate.signatures', []),
        ])->setPaper('a4', 'landscape');

        if ($certificate->file_public_id) {
            $this->cloud->delete($certificate->file_public_id);
        }

        $upload = $this->cloud->uploadContents(
            $pdf->output(),
            $certificate->certificate_number.'.pdf',
            config('npis.storage.folders.certificates', 'npis/certificates'),
        );

        $certificate->forceFill([
            'file_path' => null,
            'file_url' => $upload['url'],
            'file_public_id' => $upload['public_id'],
        ])->save();

        return $upload['url'];
    }

    public function ensureGenerated(Certificate $certificate): string
    {
        if ($certificate->file_url) {
            return $certificate->file_url;
        }

        return $this->generate($certificate);
    }
}
