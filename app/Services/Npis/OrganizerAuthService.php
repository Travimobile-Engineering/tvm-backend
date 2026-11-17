<?php

namespace App\Services\Npis;

use App\Enum\Npis\OrganizerRole;
use App\Exceptions\NpisApiException;
use App\Models\Npis\AdminSession;
use App\Models\Npis\AdminUser;
use App\Models\Npis\OrganizerAccess;
use App\Models\Npis\OrganizerAccessAttempt;
use App\Models\Npis\OrganizerAccessGrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizerAuthService
{
    public function __construct(
        private readonly OidcService $oidc,
        private readonly AuditService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function grantAccess(string $code, ?string $ip, ?string $userAgent): array
    {
        $now = now();
        $window = $now->copy()->subSeconds((int) config('npis.organizer.lockout_seconds', 900));
        $maxAttempts = (int) config('npis.organizer.max_failed_attempts', 5);

        $failed = OrganizerAccessAttempt::query()
            ->where('ip', $ip)
            ->where('succeeded', false)
            ->where('attempted_at', '>=', $window)
            ->orderBy('attempted_at')
            ->get();

        if ($failed->count() >= $maxAttempts) {
            $first = $failed->first();
            $lockout = (int) config('npis.organizer.lockout_seconds', 900);
            $retryAfter = max(1, (int) $now->diffInSeconds($first->attempted_at->copy()->addSeconds($lockout)));
            throw NpisApiException::make(
                429,
                'RATE_LIMITED',
                'Too many incorrect codes. Try again in '.ceil($retryAfter / 60).' minutes.',
                [],
                $retryAfter
            );
        }

        $access = OrganizerAccess::current();
        $normalized = $this->normalize($code);

        if (! Hash::check($normalized, $access->code_hash)) {
            OrganizerAccessAttempt::create([
                'ip' => $ip,
                'succeeded' => false,
                'attempted_at' => $now,
            ]);

            $remaining = max(0, $maxAttempts - ($failed->count() + 1));

            if ($remaining <= 0) {
                throw NpisApiException::make(
                    429,
                    'RATE_LIMITED',
                    'Too many incorrect codes. Try again in 15 minutes.',
                    [],
                    900
                );
            }

            throw NpisApiException::make(
                401,
                'ACCESS_CODE_INVALID',
                "That access code is not correct. {$remaining} ".($remaining === 1 ? 'attempt' : 'attempts').' left.',
                ['attemptsRemaining' => $remaining]
            );
        }

        return DB::transaction(function () use ($now, $ip, $userAgent): array {
            OrganizerAccessAttempt::query()->where('ip', $ip)->delete();

            $rawToken = Str::random(64);
            $expiresAt = $now->copy()->addSeconds((int) config('npis.organizer.access_grant_ttl', 28800));

            OrganizerAccessGrant::create([
                'id' => 'acc_'.Str::lower((string) Str::ulid()),
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => $expiresAt,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            $this->audit->record(
                ['type' => 'organizer', 'id' => null, 'name' => 'Organizer access', 'role' => 'pre_access'],
                'organizer.access_granted',
                'access_grant',
                null,
                'Organizer access code accepted',
                [],
                request(),
            );

            return [
                'accessToken' => $rawToken,
                'expiresAt' => $expiresAt->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function createSession(string $idToken, OrganizerAccessGrant $grant, ?string $ip, ?string $userAgent): array
    {
        $claims = $this->oidc->verify($idToken);
        $roleValue = $this->roleFromClaims($claims);

        $role = OrganizerRole::tryFrom($roleValue);
        if (! $role || ! array_key_exists($role->value, config('npis.organizer.roles', []))) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'Your identity does not carry a valid organizer role.');
        }

        $email = isset($claims['email']) ? (string) $claims['email'] : null;
        $subject = isset($claims['sub']) ? (string) $claims['sub'] : null;
        $name = (string) ($claims['name'] ?? $claims['preferred_username'] ?? $email ?? 'Organizer');

        $admin = null;

        if ($subject) {
            $admin = AdminUser::query()->where('oidc_subject', $subject)->first();
        }

        if (! $admin && $email) {
            $admin = AdminUser::query()->where('email', strtolower($email))->first();
        }

        if (! $admin) {
            if (! config('npis.oidc.auto_provision', true) || ! $email) {
                throw NpisApiException::make(403, 'FORBIDDEN', 'Your organizer account has not been provisioned.');
            }

            $admin = AdminUser::create([
                'name' => $name,
                'email' => strtolower($email),
                'role' => $role,
                'oidc_subject' => $subject,
                'is_active' => true,
            ]);
        }

        if (! $admin->is_active) {
            throw NpisApiException::make(403, 'FORBIDDEN', 'Your organizer account is not active.');
        }

        $admin->forceFill([
            'role' => $role,
            'oidc_subject' => $subject ?? $admin->oidc_subject,
            'name' => $name !== '' ? $name : $admin->name,
            'last_login_at' => now(),
        ])->save();

        return DB::transaction(function () use ($grant, $admin, $ip, $userAgent): array {
            $rawToken = Str::random(64);
            $expiresAt = now()->addSeconds((int) config('npis.organizer.admin_session_ttl', 28800));

            if ($expiresAt->greaterThan($grant->expires_at)) {
                $expiresAt = $grant->expires_at->copy();
            }

            AdminSession::create([
                'id' => 'ads_'.Str::lower((string) Str::ulid()),
                'token_hash' => hash('sha256', $rawToken),
                'admin_user_id' => $admin->id,
                'access_grant_id' => $grant->id,
                'expires_at' => $expiresAt,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);

            $this->audit->record(
                $admin,
                'organizer.signed_in',
                'admin_user',
                (string) $admin->id,
                'Organizer signed in as '.$admin->roleLabel(),
                ['role' => $admin->role->value],
                request(),
            );

            return [
                'token' => $rawToken,
                'user' => [
                    'userId' => 'usr_'.$admin->id,
                    'name' => $admin->name,
                    'role' => $admin->role->value,
                ],
                'permissions' => $admin->permissions(),
            ];
        });
    }

    public function rotateAccessCode(string $code, AdminUser $actor): int
    {
        return DB::transaction(function () use ($code, $actor): int {
            $access = OrganizerAccess::query()->lockForUpdate()->first() ?? OrganizerAccess::current();
            $access->code_hash = Hash::make($this->normalize($code));
            $access->updated_by = $actor->id;
            $access->save();

            $grants = OrganizerAccessGrant::query()->whereNull('revoked_at')->get();
            $revokedSessions = AdminSession::query()->whereNull('revoked_at')->count();

            OrganizerAccessGrant::query()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            AdminSession::query()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            OrganizerAccessAttempt::query()->delete();

            $this->audit->record(
                $actor,
                'access_code.rotated',
                'access_code',
                'organizer',
                'Changed the organizer access code and signed out all organizers',
                ['revokedGrants' => $grants->count(), 'revokedSessions' => $revokedSessions],
                request(),
            );

            return $revokedSessions;
        });
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function roleFromClaims(array $claims): string
    {
        $role = $claims['role'] ?? null;

        if ($role === null && isset($claims['roles']) && is_array($claims['roles'])) {
            $role = $claims['roles'][0] ?? null;
        }

        return is_string($role) ? $role : '';
    }

    private function normalize(string $code): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($code)));
    }
}
