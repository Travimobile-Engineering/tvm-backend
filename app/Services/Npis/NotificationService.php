<?php

namespace App\Services\Npis;

use App\Jobs\Npis\SendNotificationJob;
use App\Models\Npis\Notification;
use App\Models\Npis\NotificationTemplate;
use App\Models\Npis\Registration;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Queue a transactional notification. Notifications never block the
     * caller: a record is written and the provider call runs in the queue.
     *
     * @param  array<string, string|int|null>  $variables
     */
    public function queue(
        string $templateKey,
        string $channel,
        ?string $recipient,
        array $variables = [],
        ?string $relatedType = null,
        ?string $relatedId = null,
    ): ?Notification {
        if ($recipient === null || trim($recipient) === '') {
            return null;
        }

        $template = NotificationTemplate::query()
            ->where('key', $templateKey)
            ->where(function ($query) use ($channel): void {
                $query->where('channel', $channel)->orWhereNull('channel');
            })
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN channel IS NULL THEN 1 ELSE 0 END')
            ->first();

        $subject = $template?->subject;
        $body = $template ? $template->body : $this->fallbackBody($templateKey);

        foreach ($variables as $key => $value) {
            $body = str_replace(['{{'.$key.'}}', '{'.$key.'}'], (string) $value, $body);
            if ($subject !== null) {
                $subject = str_replace(['{{'.$key.'}}', '{'.$key.'}'], (string) $value, $subject);
            }
        }

        $notification = Notification::create([
            'id' => 'msg_'.Str::lower((string) Str::ulid()),
            'template_key' => $templateKey,
            'channel' => $channel,
            'recipient' => $recipient,
            'recipient_hash' => Notification::makeRecipientHash($channel, $recipient),
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);

        SendNotificationJob::dispatch($notification->id)->afterCommit();

        return $notification;
    }

    public function sendVerificationCode(string $channel, string $recipient, string $code, string $verificationId): ?Notification
    {
        return $this->queue(
            'verification_code',
            $channel,
            $recipient,
            ['code' => $code, 'minutes' => (int) ceil((int) config('npis.verification.code_ttl_seconds') / 60)],
            'verification',
            $verificationId,
        );
    }

    public function sendRegistrationConfirmation(Registration $registration, string $channel, ?string $recipient): ?Notification
    {
        return $this->queue(
            'registration_confirmation',
            $channel,
            $recipient,
            [
                'name' => $registration->profile?->full_name,
                'tag' => $registration->summit_tag_number,
                'status' => $registration->status->value,
            ],
            'registration',
            $registration->id,
        );
    }

    public function sendApproval(Registration $registration, string $channel, ?string $recipient): ?Notification
    {
        return $this->queue('registration_approved', $channel, $recipient, [
            'name' => $registration->profile?->full_name,
            'tag' => $registration->summit_tag_number,
        ], 'registration', $registration->id);
    }

    public function sendRejection(Registration $registration, string $channel, ?string $recipient, string $reason): ?Notification
    {
        return $this->queue('registration_rejected', $channel, $recipient, [
            'name' => $registration->profile?->full_name,
            'tag' => $registration->summit_tag_number,
            'reason' => $reason,
        ], 'registration', $registration->id);
    }

    public function sendTravelUpdated(Registration $registration, string $channel, ?string $recipient): ?Notification
    {
        return $this->queue('travel_updated', $channel, $recipient, [
            'tag' => $registration->summit_tag_number,
        ], 'registration', $registration->id);
    }

    public function sendAccommodationUpdated(Registration $registration, string $channel, ?string $recipient): ?Notification
    {
        return $this->queue('accommodation_updated', $channel, $recipient, [
            'tag' => $registration->summit_tag_number,
        ], 'registration', $registration->id);
    }

    private function fallbackBody(string $key): string
    {
        return match ($key) {
            'verification_code' => 'NPIS: Your verification code is {{code}}. It expires in {{minutes}} minutes. Police staff will never ask you for this code.',
            'registration_confirmation' => 'NPIS: Registration received. Your Summit Tag Number is {{tag}}. Status: {{status}}. Keep this number for check-in.',
            'registration_approved' => 'NPIS: Your registration {{tag}} has been approved. See you at the Police Resource Centre, Jabi.',
            'registration_rejected' => 'NPIS: Your registration {{tag}} was not approved. Reason: {{reason}}. Contact the organizing committee for help.',
            'travel_updated' => 'NPIS: Your travel itinerary for {{tag}} has been updated.',
            'accommodation_updated' => 'NPIS: Your accommodation details for {{tag}} have been updated.',
            'certificate_issued' => 'NPIS: Your certificate of participation ({{certificateNumber}}) is ready. Download it from “My registration” on the summit website.',
            'certificate_delivery' => 'NPIS: Your certificate of participation ({{certificateNumber}}) is ready. Download it from “My registration” on the summit website.',
            default => 'NPIS notification.',
        };
    }
}
