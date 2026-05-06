<?php

namespace App\Jobs;

use App\Enum\MailingEnum;
use App\Models\Mailing;
use App\Services\Mail\FallbackMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $mailingId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FallbackMailer $mailer): void
    {
        $email = Mailing::where('id', $this->mailingId)
            ->where('status', MailingEnum::PENDING)
            ->first();

        if (! $email) {
            Log::error("Mailing record not found for ID: {$this->mailingId}");

            return;
        }

        $mailableClass = $email->mailable;
        $payload = $email->payload ?? [];

        try {
            $mailableInstance = new $mailableClass(...array_values($payload));
            $sent = $mailer->send($email->email, $mailableInstance);

            if ($sent) {
                $email->update(['status' => MailingEnum::SENT]);
            } else {
                // All providers were down — FallbackMailer already logged it
                $email->increment('attempts');
                $email->update([
                    'status' => MailingEnum::FAILED,
                    'error_response' => json_encode($mailer->getErrors()),
                ]);
            }
        } catch (\Exception $e) {
            $email->increment('attempts');
            $email->update([
                'status' => MailingEnum::FAILED,
                'error_response' => $e->getMessage(),
            ]);
        }
    }
}
