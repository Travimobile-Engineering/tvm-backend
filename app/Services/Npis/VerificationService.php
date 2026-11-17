<?php

namespace App\Services\Npis;

use App\Exceptions\NpisApiException;
use App\Models\Npis\ContactVerification;
use App\Models\Npis\Notification;
use App\Models\Npis\ParticipantSession;
use App\Models\Npis\Registration;
use App\Models\Npis\SummitEvent;
use App\Models\Npis\VerifiedContact;
use App\Support\Npis\Destination;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class VerificationService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * @return array<string, mixed>
     */
    public function initiate(string $channel, string $destination, string $purpose, ?string $ip): array
    {
        $destination = Destination::normalize($channel, $destination);
        $policy = $this->policy();
        $now = now();

        $this->assertRateLimits($channel, $destination, $ip, $now);

        return DB::transaction(function () use ($channel, $destination, $purpose, $ip, $policy, $now): array {
            // Invalidate every earlier code for this destination.
            ContactVerification::query()
                ->where('destination_hash', Destination::hash($destination))
                ->whereNull('consumed_at')
                ->update(['consumed_at' => $now]);

            $contact = VerifiedContact::firstOrCreateFor($channel, $destination);

            $verification = ContactVerification::create([
                'id' => 'ver_'.Str::lower((string) Str::ulid()),
                'contact_id' => $contact->id,
                'channel' => $channel,
                'destination' => $destination,
                'destination_hash' => Destination::hash($destination),
                'purpose' => $purpose,
                'code_hash' => '',
                'expires_at' => $now->copy()->addSeconds((int) $policy['code_ttl_seconds']),
                'resend_available_at' => $now->copy()->addSeconds((int) $policy['resend_cooldown_seconds']),
                'resends_remaining' => (int) $policy['max_resends'],
                'attempts_remaining' => (int) $policy['max_attempts'],
                'delivery_status' => 'queued',
                'ip' => $ip,
            ]);

            $this->issueCode($verification, $policy);

            return $this->contract($verification->fresh());
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $verificationId): array
    {
        return $this->contract($this->activeVerification($verificationId));
    }

    /**
     * @return array<string, mixed>
     */
    public function resend(string $verificationId): array
    {
        $verification = $this->activeVerification($verificationId);
        $policy = $this->policy();
        $now = now();

        if ($verification->attempts_remaining <= 0) {
            throw NpisApiException::make(423, 'VERIFICATION_LOCKED', 'Too many incorrect attempts. Start again with a new code.');
        }

        if ($verification->resend_available_at->isFuture()) {
            $seconds = max(1, (int) $now->diffInSeconds($verification->resend_available_at));
            throw NpisApiException::make(
                429,
                'RESEND_COOLDOWN',
                "You can request another code in {$seconds} seconds.",
                [],
                $seconds
            );
        }

        if ($verification->resends_remaining <= 0) {
            throw NpisApiException::make(
                429,
                'RESEND_LIMIT_REACHED',
                'You have reached the resend limit for this request. Change the destination or try the other channel.'
            );
        }

        return DB::transaction(function () use ($verification, $policy, $now): array {
            $verification = ContactVerification::query()->lockForUpdate()->findOrFail($verification->id);
            $verification->resends_remaining = max(0, $verification->resends_remaining - 1);
            $verification->expires_at = $now->copy()->addSeconds((int) $policy['code_ttl_seconds']);
            $verification->resend_available_at = $now->copy()->addSeconds((int) $policy['resend_cooldown_seconds']);
            $verification->delivery_status = 'queued';
            $verification->save();

            $contact = $verification->contact ?? VerifiedContact::firstOrCreateFor(
                $verification->channel,
                (string) $verification->destination
            );

            $this->issueCode($verification, $policy);

            return $this->contract($verification->fresh());
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function confirm(string $verificationId, string $code, ?string $ip): array
    {
        $verification = ContactVerification::query()->find($verificationId);

        if (! $verification || $verification->consumed_at !== null) {
            throw NpisApiException::make(404, 'VERIFICATION_NOT_FOUND', 'This verification request is no longer valid. Request a new code.');
        }

        $policy = $this->policy();

        if ($verification->attempts_remaining <= 0) {
            throw NpisApiException::make(423, 'VERIFICATION_LOCKED', 'Too many incorrect attempts. Request a new code to try again.');
        }

        if (! preg_match('/^\d{'.$policy['code_length'].'}$/', $code)) {
            throw NpisApiException::validation(
                'Enter the '.$policy['code_length'].'-digit code.',
                ['code' => 'Must be '.$policy['code_length'].' digits']
            );
        }

        if ($verification->expires_at->isPast()) {
            throw NpisApiException::make(410, 'CODE_EXPIRED', 'This code has expired. Request a new code — it will arrive in a few seconds.');
        }

        if (! hash_equals($verification->code_hash, $this->hashCode($verification->id, $code))) {
            $verification->attempts_remaining = max(0, $verification->attempts_remaining - 1);
            $verification->save();

            if ($verification->attempts_remaining <= 0) {
                throw NpisApiException::make(
                    423,
                    'VERIFICATION_LOCKED',
                    'Too many incorrect attempts. Request a new code to try again.',
                    ['attemptsRemaining' => 0]
                );
            }

            $remaining = $verification->attempts_remaining;
            throw NpisApiException::make(
                400,
                'CODE_INCORRECT',
                "That code is not correct. {$remaining} ".($remaining === 1 ? 'attempt' : 'attempts').' left.',
                ['attemptsRemaining' => $remaining]
            );
        }

        return DB::transaction(function () use ($verification): array {
            $verification = ContactVerification::query()->lockForUpdate()->findOrFail($verification->id);

            if ($verification->consumed_at !== null) {
                throw NpisApiException::make(404, 'VERIFICATION_NOT_FOUND', 'This verification request is no longer valid. Request a new code.');
            }

            $verifiedAt = now();
            $verification->consumed_at = $verifiedAt;
            $verification->delivery_status = 'delivered';
            $verification->save();

            ContactVerification::query()
                ->where('destination_hash', $verification->destination_hash)
                ->where('id', '!=', $verification->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => $verifiedAt]);

            $contact = VerifiedContact::query()->lockForUpdate()->findOrFail($verification->contact_id);
            $contact->verified_at ??= $verifiedAt;
            $contact->last_verified_at = $verifiedAt;
            $contact->save();

            $rawToken = Str::random(64);
            ParticipantSession::create([
                'id' => 'psn_'.Str::lower((string) Str::ulid()),
                'contact_id' => $contact->id,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => now()->addSeconds((int) config('npis.participant_session_ttl', 7200)),
                'revoked_at' => null,
            ]);

            $existing = $this->resolveRegistration($contact);

            return [
                'sessionToken' => $rawToken,
                'sessionExpiresAt' => now()->addSeconds((int) config('npis.participant_session_ttl', 7200))->toIso8601String(),
                'contact' => [
                    'channel' => $contact->channel,
                    'destination' => (string) $contact->destination,
                    'verifiedAt' => $verifiedAt->toIso8601String(),
                ],
                'existingRegistration' => $existing instanceof Registration ? [
                    'registrationId' => $existing->id,
                    'summitTagNumber' => $existing->summit_tag_number,
                    'status' => $existing->status->value,
                    'source' => $existing->source->value,
                    'isComplete' => ($existing->status->value) !== 'incomplete',
                ] : null,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(ContactVerification $verification): array
    {
        return [
            'verificationId' => $verification->id,
            'channel' => $verification->channel,
            'purpose' => $verification->purpose,
            'maskedDestination' => $verification->maskedDestination(),
            'codeLength' => (int) $this->policy()['code_length'],
            'expiresAt' => $verification->expires_at->toIso8601String(),
            'resendAvailableAt' => $verification->resend_available_at->toIso8601String(),
            'resendsRemaining' => $verification->resends_remaining,
            'attemptsRemaining' => $verification->attempts_remaining,
            'deliveryStatus' => $this->deliveryStatus($verification),
        ];
    }

    public function resolveRegistration(VerifiedContact $contact): ?Registration
    {
        $registration = Registration::query()
            ->where('verified_contact_id', $contact->id)
            ->whereIn('status', ['incomplete', 'pending_approval', 'approved', 'rejected'])
            ->orderByDesc('submitted_at')
            ->first();

        if (! $registration) {
            $registration = Registration::query()
                ->where('verified_contact_id', $contact->id)
                ->orderByDesc('submitted_at')
                ->first();
        }

        $guard = 0;
        while ($registration?->merged_into_registration_id && $guard < 10) {
            $registration = Registration::query()->find($registration->merged_into_registration_id);
            $guard++;
        }

        return $registration;
    }

    /**
     * @return array<string, mixed>
     */
    private function policy(): array
    {
        $eventPolicy = [];
        try {
            $event = SummitEvent::query()->find(config('npis.event_id'));
            $eventPolicy = (array) ($event?->policies['verification'] ?? []);
        } catch (Throwable) {
            $eventPolicy = [];
        }

        $defaults = [
            'code_length' => (int) config('npis.verification.code_length', 5),
            'code_ttl_seconds' => (int) config('npis.verification.code_ttl_seconds', 600),
            'resend_cooldown_seconds' => (int) config('npis.verification.resend_cooldown_seconds', 60),
            'max_resends' => (int) config('npis.verification.max_resends', 3),
            'max_attempts' => (int) config('npis.verification.max_attempts', 5),
        ];

        return [
            'code_length' => (int) ($eventPolicy['codeLength'] ?? $defaults['code_length']),
            'code_ttl_seconds' => (int) ($eventPolicy['codeTtlSeconds'] ?? $defaults['code_ttl_seconds']),
            'resend_cooldown_seconds' => (int) ($eventPolicy['resendCooldownSeconds'] ?? $defaults['resend_cooldown_seconds']),
            'max_resends' => (int) ($eventPolicy['maxResends'] ?? $defaults['max_resends']),
            'max_attempts' => (int) ($eventPolicy['maxAttempts'] ?? $defaults['max_attempts']),
        ];
    }

    private function assertRateLimits(string $channel, string $destination, ?string $ip, Carbon $now): void
    {
        $hash = Destination::hash($destination);
        $destinationCount = ContactVerification::query()
            ->where('destination_hash', $hash)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();

        if ($destinationCount >= (int) config('npis.verification.challenges_per_destination_per_hour', 5)) {
            throw NpisApiException::make(
                429,
                'RATE_LIMITED',
                'Too many codes have been requested for this '.($channel === 'sms' ? 'number' : 'address').'. Try again later or use a different channel.',
                [],
                3600
            );
        }

        if ($ip !== null) {
            $ipCount = ContactVerification::query()
                ->where('ip', $ip)
                ->where('created_at', '>=', $now->copy()->subHour())
                ->count();

            if ($ipCount >= (int) config('npis.verification.challenges_per_ip_per_hour', 20)) {
                throw NpisApiException::make(429, 'RATE_LIMITED', 'Too many verification requests from this network. Try again later.', [], 3600);
            }
        }
    }

    private function activeVerification(string $verificationId): ContactVerification
    {
        $verification = ContactVerification::query()
            ->where('id', $verificationId)
            ->whereNull('consumed_at')
            ->first();

        if (! $verification) {
            throw NpisApiException::make(404, 'VERIFICATION_NOT_FOUND', 'This verification request is no longer valid. Request a new code.');
        }

        return $verification;
    }

    /**
     * @param  array{code_length: int, code_ttl_seconds: int, resend_cooldown_seconds: int, max_resends: int, max_attempts: int}  $policy
     */
    private function issueCode(ContactVerification $verification, array $policy): void
    {
        $length = (int) ($policy['code_length'] ?? 5);
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        $verification->code_hash = $this->hashCode($verification->id, $code);
        $verification->delivery_status = 'queued';
        $verification->save();

        $this->notifications->queue(
            'verification_code',
            $verification->channel,
            (string) $verification->destination,
            [
                'code' => $code,
                'minutes' => (int) ceil(((int) $policy['code_ttl_seconds']) / 60),
            ],
            'verification',
            $verification->id,
        );
    }

    private function hashCode(string $verificationId, string $code): string
    {
        return hash_hmac('sha256', $code, config('app.key').'|'.$verificationId);
    }

    private function deliveryStatus(ContactVerification $verification): string
    {
        $notification = Notification::query()
            ->where('related_type', 'verification')
            ->where('related_id', $verification->id)
            ->latest()
            ->first();

        if (! $notification) {
            return $verification->delivery_status ?: 'queued';
        }

        return match ($notification->status) {
            'sent' => 'sent',
            'delivered' => 'delivered',
            'failed' => 'failed',
            default => 'queued',
        };
    }
}
