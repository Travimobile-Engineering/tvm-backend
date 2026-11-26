<?php

namespace App\Services\Emf\Field;

use App\Exceptions\NpisApiException;
use App\Models\Emf\AuditEvent;
use App\Models\Emf\Officer;
use App\Models\Emf\OfficerSession;
use App\Models\Emf\SupportRequest;
use App\Services\Emf\EmfAuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmfFieldAccountService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(Officer $officer): array
    {
        $officer->loadMissing(['dutyPoint', 'state', 'lga']);
        $activeSessions = OfficerSession::query()
            ->where('officer_id', $officer->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();
        $shiftEnd = $this->shiftEnd($officer);

        return [
            'officer' => [
                'officerId' => $officer->id,
                'fullName' => $officer->full_name,
                'rank' => $officer->rank,
                'serviceNumber' => $officer->service_number,
                'email' => $officer->email,
                'phoneMasked' => $this->mask($officer->phone),
                'ninMasked' => $this->mask($officer->nin),
                'photoUrl' => $officer->photo_url,
                'status' => $officer->status,
                'stateCommand' => $officer->state?->name,
                'division' => $officer->division,
                'dutyPoint' => $officer->dutyPoint?->name,
                'lga' => $officer->lga?->name,
                'reportsTo' => $officer->reports_to,
                'opsRoom' => $officer->ops_room ?? 'State command operations room',
                'mustChangePassword' => $officer->must_change_password,
                'activatedAt' => $officer->activated_at?->toIso8601String(),
                'shiftStart' => $officer->dutyPoint?->shift_start,
                'shiftEnd' => $officer->dutyPoint?->shift_end,
                'shiftEndsAt' => $shiftEnd?->toIso8601String(),
                'shiftRemainingSeconds' => $shiftEnd instanceof Carbon ? max(0, (int) now()->diffInSeconds($shiftEnd, false)) : null,
            ],
            'security' => [
                'passwordChangedAt' => $officer->password_changed_at?->toIso8601String(),
                'twoFactorEnabled' => $officer->two_factor_enabled,
                'biometricEnabled' => $officer->biometric_enabled,
                'activeSessions' => $activeSessions,
                'backupCodesRemaining' => count($officer->backup_codes ?? []),
            ],
            'preferences' => $officer->field_notification_preferences ?? $this->defaultPreferences(),
            'appearance' => ['theme' => $officer->theme],
            'recentActivity' => $this->activityPreview($officer),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateSecurity(Officer $officer, array $payload, Request $request): array
    {
        $twoFactor = array_key_exists('twoFactorEnabled', $payload) ? (bool) $payload['twoFactorEnabled'] : $officer->two_factor_enabled;
        $biometric = array_key_exists('biometricEnabled', $payload) ? (bool) $payload['biometricEnabled'] : $officer->biometric_enabled;
        $setup = null;
        $plainCodes = null;

        if ($twoFactor && ! $officer->two_factor_secret) {
            $secret = Str::upper(Str::random(32));
            $setup = [
                'secret' => $secret,
                'otpauthUrl' => 'otpauth://totp/'.rawurlencode('NTEM Field:'.$officer->service_number).'?secret='.$secret.'&issuer='.rawurlencode('Nigeria Police Force'),
            ];
            $officer->two_factor_secret = $secret;
        }

        if (! $twoFactor) {
            $officer->two_factor_secret = null;
            $officer->backup_codes = null;
        }

        if ($twoFactor && (($payload['generateBackupCodes'] ?? false) || empty($officer->backup_codes))) {
            $plainCodes = collect(range(1, 8))->map(fn (): string => Str::upper(Str::random(10)))->all();
            $officer->backup_codes = array_map(fn (string $code): string => hash('sha256', $code), $plainCodes);
        }

        $officer->forceFill([
            'two_factor_enabled' => $twoFactor,
            'biometric_enabled' => $biometric,
            'two_factor_secret' => $officer->two_factor_secret,
            'backup_codes' => $officer->backup_codes,
        ])->save();

        $this->audit->record($officer, 'field.security_updated', 'officer', $officer->id, 'Updated security settings', 'done', [], $request);

        return [
            'security' => $this->profile($officer->fresh())['security'],
            'twoFactorSetup' => $setup,
            'backupCodes' => $plainCodes,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateAppearance(Officer $officer, array $payload, Request $request): array
    {
        $theme = (string) $payload['theme'];
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            throw NpisApiException::validation('Choose a valid theme.', ['theme' => 'light, dark or system']);
        }

        $officer->forceFill(['theme' => $theme])->save();
        $this->audit->record($officer, 'field.appearance_updated', 'officer', $officer->id, 'Changed appearance to '.$theme, 'done', [], $request);

        return ['theme' => $theme];
    }

    /**
     * @param  array<string, bool>  $preferences
     * @return array<string, bool>
     */
    public function updateNotifications(Officer $officer, array $preferences, Request $request): array
    {
        $merged = array_merge($this->defaultPreferences(), $preferences);
        $officer->forceFill(['field_notification_preferences' => $merged])->save();
        $this->audit->record($officer, 'field.notifications_updated', 'officer', $officer->id, 'Updated field notification preferences', 'done', [], $request);

        return $merged;
    }

    public function changePassword(Officer $officer, string $currentPassword, string $newPassword, ?string $currentSessionId, Request $request): void
    {
        if (! Hash::check($currentPassword, $officer->password_hash)) {
            throw NpisApiException::make(422, 'VALIDATION_FAILED', 'The current password is incorrect.', [
                'fields' => ['currentPassword' => 'Incorrect password'],
            ]);
        }

        if (Hash::check($newPassword, $officer->password_hash)) {
            throw NpisApiException::validation('Choose a password you have not used before.', ['newPassword' => 'Must be different']);
        }

        $officer->forceFill([
            'password_hash' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
        ])->save();

        OfficerSession::query()
            ->where('officer_id', $officer->id)
            ->when($currentSessionId, fn ($query, string $id) => $query->where('id', '!=', $id))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $this->audit->record($officer, 'field.password_changed', 'officer', $officer->id, 'Changed password', 'done', [], $request);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessions(Officer $officer, ?string $currentSessionId = null): array
    {
        return OfficerSession::query()
            ->where('officer_id', $officer->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OfficerSession $session): array => [
                'sessionId' => $session->id,
                'ip' => $session->ip,
                'userAgent' => $session->user_agent,
                'createdAt' => $session->created_at?->toIso8601String(),
                'expiresAt' => $session->expires_at->toIso8601String(),
                'current' => $currentSessionId !== null && $session->id === $currentSessionId,
            ])->values()->all();
    }

    public function revokeSession(Officer $officer, string $sessionId, Request $request): void
    {
        $session = OfficerSession::query()->where('officer_id', $officer->id)->find($sessionId);
        if (! $session) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Session not found.');
        }

        $session->forceFill(['revoked_at' => now()])->save();
        $this->audit->record($officer, 'field.session_revoked', 'officer_session', $session->id, 'Signed out a device', 'done', [], $request);
    }

    public function revokeOtherSessions(Officer $officer, ?string $currentSessionId, Request $request): int
    {
        $count = OfficerSession::query()
            ->where('officer_id', $officer->id)
            ->when($currentSessionId, fn ($query, string $id) => $query->where('id', '!=', $id))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $this->audit->record($officer, 'field.sessions_revoked', 'officer', $officer->id, 'Signed out other devices', 'done', ['count' => $count], $request);

        return $count;
    }

    public function revokeAllSessions(Officer $officer, Request $request): int
    {
        $count = OfficerSession::query()
            ->where('officer_id', $officer->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $this->audit->record($officer, 'field.all_sessions_revoked', 'officer', $officer->id, 'Signed out all devices', 'done', ['count' => $count], $request);

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function activity(Officer $officer, string $filter, int $page, int $pageSize): array
    {
        $query = AuditEvent::query()->where('actor_id', $officer->id);
        $this->applyActivityFilter($query, $filter);
        $total = (clone $query)->count();

        return [
            'data' => $query->orderByDesc('occurred_at')->offset(($page - 1) * $pageSize)->limit($pageSize)->get()
                ->map(fn (AuditEvent $event): array => [
                    'activityId' => $event->id,
                    'action' => $event->action,
                    'detail' => $event->detail,
                    'result' => $event->result,
                    'at' => $event->occurred_at->toIso8601String(),
                ])->values()->all(),
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function support(): array
    {
        return [
            'opsRoom' => [
                'label' => 'Call the ops room',
                'phone' => config('emf.field.ops_room_phone', '+234 803 000 0000'),
                'available' => '24/7',
            ],
            'topics' => [
                ['key' => 'no_signal', 'title' => 'Reporting when there’s no signal', 'summary' => 'Reports are saved on your phone and send automatically when signal returns.'],
                ['key' => 'no_id', 'title' => 'A passenger won’t show their ID', 'summary' => 'Flag the seat, record the reason and keep the passenger in sight. Do not release the bus.'],
                ['key' => 'grant_ended', 'title' => 'My grant ended mid-shift', 'summary' => 'Call the ops room. They can extend the grant without restarting your session.'],
                ['key' => 'when_reveal', 'title' => 'When can I reveal a NIN?', 'summary' => 'Reveal only when needed for an active check. Every reveal is logged with your reason.'],
                ['key' => 'app_problem', 'title' => 'Report an app problem', 'summary' => 'Send app logs to NTEM support. Include what you were doing when it happened.'],
            ],
            'appVersion' => '1.0',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSupportRequest(Officer $officer, array $payload, Request $request): SupportRequest
    {
        $type = (string) ($payload['type'] ?? 'app_problem');
        if (! in_array($type, ['app_problem', 'correction', 'help'], true)) {
            $type = 'app_problem';
        }

        $support = SupportRequest::create([
            'id' => 'sup_'.Str::lower((string) Str::ulid()),
            'officer_id' => $officer->id,
            'request_type' => $type,
            'subject' => $payload['subject'] ?? Str::headline($type),
            'message' => $payload['message'] ?? 'No details provided.',
            'app_version' => $payload['appVersion'] ?? null,
            'device_info' => $payload['deviceInfo'] ?? [],
            'status' => 'open',
        ]);

        $this->audit->record($officer, 'field.support_requested', 'support_request', $support->id, 'Raised '.$type.' request', 'done', [], $request);

        return $support;
    }

    /**
     * @return array<string, bool>
     */
    private function defaultPreferences(): array
    {
        return [
            'watchlistHitsOnRoute' => true,
            'busApproachingMyPost' => true,
            'corridorAPBs' => true,
            'reportStatusChanges' => true,
            'approvals' => true,
            'shiftEndingReminder' => true,
            'appUpdates' => false,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activityPreview(Officer $officer): array
    {
        return AuditEvent::query()->where('actor_id', $officer->id)->orderByDesc('occurred_at')->limit(3)->get()
            ->map(fn (AuditEvent $event): array => [
                'action' => $event->action,
                'detail' => $event->detail,
                'at' => $event->occurred_at->toIso8601String(),
            ])->values()->all();
    }

    /**
     * @param  Builder<AuditEvent>  $query
     */
    private function applyActivityFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'checks' => $query->where(fn ($builder) => $builder->where('action', 'like', 'field.%')->orWhere('action', 'like', 'manifest.%')),
            'reports' => $query->where(fn ($builder) => $builder->where('action', 'like', 'field.report%')->orWhere('action', 'like', 'incident.%')),
            'account' => $query->where(fn ($builder) => $builder->where('action', 'like', 'officer.%')->orWhere('action', 'like', 'field.%session%')->orWhere('action', 'like', 'field.%password%')),
            default => null,
        };
    }

    private function shiftEnd(Officer $officer): ?Carbon
    {
        $end = $officer->dutyPoint?->shift_end;

        return $end ? Carbon::parse(now()->toDateString().' '.$end) : null;
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return str_repeat('•', max(0, strlen($value) - (str_contains($value, '@') ? 0 : 4))).substr($value, -4);
    }
}
