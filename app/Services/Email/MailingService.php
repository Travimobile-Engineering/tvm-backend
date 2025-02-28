<?php

namespace App\Services\Email;

use App\Enum\MailingEnum;
use App\Models\Mailing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailingService
{
    public function sendEmails(int $batchSize = 15)
    {
        DB::transaction(function () use ($batchSize) {
            $emails = Mailing::where('status', MailingEnum::PENDING)
                            ->where('attempts', '<', 3)
                            ->limit($batchSize)
                            ->lockForUpdate()
                            ->get();

            foreach ($emails as $email) {
                try {
                    if (!class_exists($email->mailable)) {
                        Log::error("Mailable class {$email->mailable} not found.");
                        $email->update(['status' => MailingEnum::FAILED]);
                        continue;
                    }

                    $payload = $email->payload ?? [];
                    $mailableInstance = new $email->mailable(...array_values($payload));

                    Mail::to($email->email)->send($mailableInstance);

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
        });
    }
}

