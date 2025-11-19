<?php

namespace App\Jobs;

use App\Enum\MailingEnum;
use App\Models\Mailing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessMail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $mailingId
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        logger()->info('Process mail');

        logger()->info('Process mail id:' . $this->mailingId);

        $email = Mailing::where('id', $this->mailingId)
            ->where('status', MailingEnum::PENDING)
            ->where('attempts', '<', 3)
            ->first();

        if (!$email) {
            Log::error("Mailing record not found for ID: {$this->mailingId}");
            return;
        }

        logger()->info('mailing record found.');

        $mailableClass = $email->mailable;
        $payload = $email->payload ?? [];

        try {

            logger()->info('Payload:' . json_encode($payload));

            $mailableInstance = new $mailableClass(...array_values($payload));
            Mail::to($email->email)->send($mailableInstance);

            logger()->info('Email sent!');

            $email->update(['status' => MailingEnum::SENT]);

        } catch (\Exception $e) {
            Log::error("Email failed to send: " . $e->getMessage());
            $email->increment('attempts');

            if ($email->attempts >= $email->max_attempts) {
                $email->update([
                    'status' => MailingEnum::FAILED,
                    'error_response' => $e->getMessage()
                ]);
            }
        }
    }
}
