<?php

namespace App\Services\Email;

use App\Enum\MailingEnum;
use App\Jobs\ProcessMail;
use App\Models\Mailing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MailingService
{
    public function sendEmails(int $batchSize = 15)
    {
        dispatch(new ProcessMail($batchSize));
    }

    public function sendEmail($batchSize = 15): void
    {
        DB::transaction(function () use ($batchSize): void {
            $emails = Mailing::where('status', MailingEnum::PENDING)
                ->where('attempts', '<', 3)
                ->limit($batchSize)
                ->get();

            foreach ($emails as $email) {
                try {
                    if (! class_exists($email->mailable)) {
                        logger()->error("Mailable class {$email->mailable} not found.");
                        $email->update([
                            'status' => MailingEnum::FAILED,
                            'error_response' => 'Mailable class not found',
                        ]);

                        continue;
                    }

                    $payload = $email->payload ?? [];

                    if (empty($payload)) {
                        logger()->error("Payload for mailable class {$email->mailable} is empty.");
                        $email->update([
                            'status' => MailingEnum::FAILED,
                            'error_response' => 'Payload is empty',
                        ]);

                        continue;
                    }

                    $mailableInstance = new $email->mailable(...array_values($payload));
                    Mail::to($email->email)->send($mailableInstance);

                    $email->update(['status' => MailingEnum::SENT]);
                } catch (\Exception $e) {
                    logger()->error('Email failed to send: '.$e->getMessage());

                    $email->increment('attempts');
                    $email->update([
                        'status' => MailingEnum::FAILED,
                        'error_response' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}

