<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NpisApiException;
use App\Models\Npis\Certificate;
use App\Models\Npis\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationWebhookController extends NpisApiController
{
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $secret = (string) config('npis.notifications.webhook_secret');

        if ($secret === '') {
            throw NpisApiException::make(503, 'WEBHOOK_NOT_CONFIGURED', 'Notification callbacks are not configured.');
        }

        $signature = (string) $request->header('X-NPIS-Signature', '');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Invalid notification callback signature.');
        }

        $messageId = (string) $request->input('messageId', '');
        $status = (string) $request->input('status', '');

        if ($messageId === '' || ! in_array($status, ['sent', 'delivered', 'failed'], true)) {
            throw NpisApiException::validation('A messageId and a valid status are required.', [
                'messageId' => $messageId === '' ? 'Required' : null,
                'status' => in_array($status, ['sent', 'delivered', 'failed'], true) ? null : 'Invalid',
            ]);
        }

        $notification = Notification::query()
            ->where('provider_message_id', $messageId)
            ->orWhere('id', $messageId)
            ->first();

        if (! $notification) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Notification not found.');
        }

        $notification->forceFill([
            'provider' => $provider,
            'status' => $status,
            'provider_response' => array_merge($notification->provider_response ?? [], $request->all()),
            'delivered_at' => $status === 'delivered' ? now() : $notification->delivered_at,
            'failed_at' => $status === 'failed' ? now() : null,
            'error' => $request->input('error'),
        ])->save();

        if ($notification->related_type === 'certificate' && $notification->related_id) {
            $certificate = Certificate::query()->find($notification->related_id);

            if ($certificate) {
                $certificate->forceFill([
                    'delivery_status' => $status,
                    'last_error' => $request->input('error'),
                ])->save();
            }
        }

        return $this->success(['received' => true], 'Notification callback received successfully');
    }
}
