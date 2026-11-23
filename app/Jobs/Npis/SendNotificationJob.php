<?php

namespace App\Jobs\Npis;

use App\Contracts\SMS;
use App\Models\Npis\Notification;
use App\Models\Npis\Registration;
use App\Services\Npis\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $notificationId) {}

    public function handle(SMS $sms, NotificationService $notifications): void
    {
        $notification = Notification::find($this->notificationId);

        if (! $notification || $notification->status === 'delivered' || $notification->status === 'sent') {
            return;
        }

        try {
            if ($notification->channel === 'sms') {
                $response = $sms->sendSms((string) $notification->recipient, (string) $notification->body);

                $notification->forceFill([
                    'provider' => 'termii',
                    'provider_message_id' => $response['message_id'] ?? $response['id'] ?? null,
                    'provider_response' => $response,
                    'status' => 'sent',
                    'sent_at' => now(),
                ])->save();
            } else {
                $notifications->deliverEmail($notification);
            }
        } catch (Throwable $exception) {
            $notification->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'error' => $exception->getMessage(),
            ])->save();

            Log::warning('NPIS notification failed', [
                'notification_id' => $notification->id,
                'template' => $notification->template_key,
                'channel' => $notification->channel,
            ]);

            $this->fallbackToEmail($notification);

            throw $exception;
        }
    }

    private function fallbackToEmail(Notification $notification): void
    {
        if (! config('npis.notifications.sms_email_fallback') || $notification->channel !== 'sms') {
            return;
        }

        if ($notification->related_type !== 'registration' || $notification->related_id === null) {
            return;
        }

        $registration = Registration::with('contact')->find($notification->related_id);
        $email = $registration?->contact?->channel === 'email'
            ? $registration->contact->destination
            : null;

        if (! $email) {
            return;
        }

        $fallback = Notification::create([
            'id' => 'msg_'.strtolower((string) str()->ulid()),
            'template_key' => $notification->template_key,
            'channel' => 'email',
            'recipient' => $email,
            'recipient_hash' => Notification::makeRecipientHash('email', $email),
            'subject' => $notification->subject,
            'body' => (string) $notification->body,
            'status' => 'queued',
            'related_type' => $notification->related_type,
            'related_id' => $notification->related_id,
        ]);

        self::dispatch($fallback->id)->afterCommit();
    }
}
