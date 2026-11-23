<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Notification;
use App\Models\Emf\NotificationPreference;
use App\Models\Emf\Officer;
use App\Models\Emf\OfficerSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmfNotificationService
{
    public function __construct(
        private readonly EmfAuditService $audit,
        private readonly EmfAuthService $auth,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inbox(Officer $officer, ?string $filter): array
    {
        $query = Notification::query()->where('officer_id', $officer->id);
        $this->applyFilter($query, $filter);

        return [
            'data' => $query->orderByDesc('created_at')->limit(100)->get()->map(fn (Notification $notification): array => [
                'notificationId' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'needsAction' => $notification->needs_action,
                'status' => $notification->status,
                'link' => $notification->link,
                'createdAt' => $notification->created_at?->toIso8601String(),
                'readAt' => $notification->read_at?->toIso8601String(),
            ])->all(),
            'counts' => [
                'all' => Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')->count(),
                'needsAction' => Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')->where('needs_action', true)->count(),
                'alerts' => Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')->whereIn('type', ['critical', 'alert'])->count(),
                'approvals' => Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')->where('type', 'approval')->count(),
                'expiries' => Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')->where('type', 'expiry')->count(),
            ],
        ];
    }

    public function markRead(Notification $notification, Officer $officer): Notification
    {
        if ($notification->officer_id !== $officer->id) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Notification not found.');
        }

        $notification->forceFill(['status' => 'read', 'read_at' => now()])->save();

        return $notification->fresh();
    }

    public function markAllRead(Officer $officer): int
    {
        return Notification::query()->where('officer_id', $officer->id)->where('status', 'unread')
            ->update(['status' => 'read', 'read_at' => now()]);
    }

    public function push(string $officerId, string $type, string $title, string $body, bool $needsAction = false, ?string $link = null): Notification
    {
        return Notification::create([
            'id' => 'not_'.Str::lower((string) Str::ulid()),
            'officer_id' => $officerId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'needs_action' => $needsAction,
            'status' => 'unread',
            'link' => $link,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(Officer $officer): array
    {
        $preferences = NotificationPreference::query()->where('officer_id', $officer->id)->first();

        return [
            'profile' => $this->auth->officerContract($officer),
            'notifications' => $preferences->preferences ?? $this->defaultPreferences(),
            'preferenceOptions' => [
                'events' => ['criticalAlert', 'flaggedManifest', 'incidentUpdate', 'caseApproval', 'grantExpiry', 'deviceApproval', 'weeklyDigest'],
                'channels' => ['inApp', 'sms', 'email', 'push'],
            ],
            'sessions' => $this->sessions($officer),
            'languages' => [
                ['code' => 'en', 'label' => 'English'],
                ['code' => 'ha', 'label' => 'Hausa'],
                ['code' => 'ig', 'label' => 'Igbo'],
                ['code' => 'yo', 'label' => 'Yoruba'],
            ],
            'sessionTimeoutMinutes' => 15,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateProfile(Officer $officer, array $payload, Request $request): Officer
    {
        $officer->fill(array_filter([
            'full_name' => $payload['fullName'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'language' => $payload['language'] ?? null,
        ], static fn ($value): bool => $value !== null))->save();

        $this->audit->record($officer, 'settings.profile_updated', 'officer', $officer->id, 'Updated profile settings', 'done', [], $request);

        return $officer->fresh();
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function updatePreferences(Officer $officer, array $preferences, Request $request): array
    {
        $record = NotificationPreference::query()->updateOrCreate(
            ['officer_id' => $officer->id],
            ['preferences' => array_merge($this->defaultPreferences(), $preferences)],
        );

        $this->audit->record($officer, 'settings.notifications_updated', 'officer', $officer->id, 'Updated notification preferences', 'done', [], $request);

        return $record->preferences;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessions(Officer $officer): array
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
                'current' => false,
            ])->all();
    }

    public function revokeSession(Officer $officer, string $sessionId, Request $request): void
    {
        $session = OfficerSession::query()->where('officer_id', $officer->id)->find($sessionId);

        if (! $session) {
            throw NpisApiException::make(404, 'NOT_FOUND', 'Session not found.');
        }

        $session->forceFill(['revoked_at' => now()])->save();
        $this->audit->record($officer, 'settings.session_revoked', 'officer_session', $session->id, 'Revoked a signed-in device', 'done', [], $request);
    }

    /**
     * @return array<string, mixed>
     */
    public function help(): array
    {
        return [
            'topics' => [
                ['key' => 'manifest_check', 'title' => 'How to check a manifest', 'summary' => 'Open Manifests, choose New check, enter the trip details and passenger list, then submit.'],
                ['key' => 'alert_escalation', 'title' => 'When to escalate an alert', 'summary' => 'Escalate when a watchlist match is confirmed by NIN and an operational response is required.'],
                ['key' => 'case_closure', 'title' => 'Case closure approvals', 'summary' => 'Owners submit for closure; a supervisor approves, sends back, or requests a reopen.'],
                ['key' => 'access_grants', 'title' => 'Access grants', 'summary' => 'Grants are time-boxed, mode-scoped and conflict-checked before creation.'],
            ],
            'support' => ['email' => 'manifest-support@npf.gov.ng', 'phone' => '+234 800 000 0000'],
        ];
    }

    /**
     * @param  Builder<Notification>  $query
     */
    private function applyFilter(Builder $query, ?string $filter): void
    {
        match ($filter) {
            'needs_action' => $query->where('needs_action', true)->where('status', 'unread'),
            'alerts' => $query->whereIn('type', ['critical', 'alert']),
            'approvals' => $query->where('type', 'approval'),
            'expiries' => $query->where('type', 'expiry'),
            default => null,
        };
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function defaultPreferences(): array
    {
        return [
            'criticalAlert' => ['inApp' => true, 'sms' => true, 'email' => true, 'push' => true],
            'flaggedManifest' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => true],
            'incidentUpdate' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => true],
            'caseApproval' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => false],
            'grantExpiry' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => false],
            'deviceApproval' => ['inApp' => true, 'sms' => false, 'email' => false, 'push' => false],
            'weeklyDigest' => ['inApp' => false, 'sms' => false, 'email' => true, 'push' => false],
        ];
    }
}
