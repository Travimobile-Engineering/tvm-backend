<?php

namespace App\Jobs\Npis;

use App\Models\Npis\Certificate;
use App\Services\Npis\CertificatePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCertificatePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $certificateId) {}

    public function handle(CertificatePdfService $pdfs): void
    {
        $certificate = Certificate::find($this->certificateId);

        if (! $certificate) {
            return;
        }

        $pdfs->ensureGenerated($certificate);

        SendCertificateDeliveryJob::dispatch($certificate->id)->afterCommit();
    }
}
