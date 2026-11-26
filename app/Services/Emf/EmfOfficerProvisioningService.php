<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\Officer;
use App\Models\Emf\OfficerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmfOfficerProvisioningService
{
    public function __construct(
        private readonly EmfAuditService $audit,
        private readonly EmfAuthService $auth,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload, Officer $actor, Request $request): array
    {
        $roles = array_keys((array) config('emf.roles', []));
        $role = (string) $payload['role'];

        if (! in_array($role, $roles, true)) {
            throw NpisApiException::validation('Choose a valid officer role.', ['role' => 'Invalid role']);
        }

        $sendInvite = (bool) ($payload['sendInvite'] ?? true);
        $temporaryPassword = $sendInvite ? null : $this->temporaryPassword();
        $rawToken = $sendInvite ? Str::random(64) : null;
        $expiresAt = $sendInvite
            ? now()->addSeconds((int) config('emf.provisioning.invitation_ttl', 172800))
            : null;

        $officer = Officer::create([
            'id' => 'off_'.Str::lower((string) Str::ulid()),
            'service_number' => $payload['serviceNumber'],
            'rank' => $payload['rank'],
            'full_name' => $payload['fullName'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'unit' => $payload['unit'] ?? 'Manifest Enforcement',
            'state_code' => $payload['stateCode'],
            'lga_id' => $payload['lgaId'] ?? null,
            'duty_point_id' => $payload['dutyPointId'] ?? null,
            'role' => $role,
            'status' => $sendInvite ? 'pending_activation' : 'off_duty',
            'language' => $payload['language'] ?? 'en',
            'password_hash' => Hash::make($sendInvite ? Str::random(64) : (string) $temporaryPassword),
            'watchlist_edit_code_hash' => null,
            'nin' => $payload['nin'] ?? null,
            'division' => $payload['division'] ?? null,
            'reports_to' => $payload['reportsTo'] ?? null,
            'ops_room' => $payload['opsRoom'] ?? null,
            'photo_url' => $payload['photoUrl'] ?? null,
            'invitation_token_hash' => $rawToken ? hash('sha256', $rawToken) : null,
            'invitation_expires_at' => $expiresAt,
            'invited_at' => now(),
            'activated_at' => $sendInvite ? null : now(),
            'must_change_password' => ! $sendInvite,
            'temporary_password_expires_at' => $sendInvite ? null : now()->addSeconds((int) config('emf.provisioning.temporary_password_ttl', 86400)),
            'created_by' => $actor->id,
        ]);

        $this->audit->record($actor, 'officer.provisioned', 'officer', $officer->id, 'Provisioned officer account '.$officer->service_number, 'done', [
            'invite' => $sendInvite,
            'role' => $role,
        ], $request);

        return $this->provisioningResponse($officer, $rawToken, $temporaryPassword);
    }

    /**
     * @return array<string, mixed>
     */
    public function resendInvite(Officer $officer, Officer $actor, Request $request): array
    {
        if ((string) $officer->status !== 'pending_activation') {
            throw NpisApiException::make(409, 'ALREADY_ACTIVATED', 'This account is already active. Use password reset instead.');
        }

        $rawToken = Str::random(64);
        $officer->forceFill([
            'invitation_token_hash' => hash('sha256', $rawToken),
            'invitation_expires_at' => now()->addSeconds((int) config('emf.provisioning.invitation_ttl', 172800)),
            'invited_at' => now(),
            'password_hash' => Hash::make(Str::random(64)),
        ])->save();

        $this->audit->record($actor, 'officer.invite_resent', 'officer', $officer->id, 'Resent activation invite to '.$officer->service_number, 'done', [], $request);

        return $this->provisioningResponse($officer->fresh(), $rawToken, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function resetPassword(Officer $officer, Officer $actor, Request $request): array
    {
        $temporaryPassword = $this->temporaryPassword();
        $expiresAt = now()->addSeconds((int) config('emf.provisioning.temporary_password_ttl', 86400));

        $officer->forceFill([
            'password_hash' => Hash::make($temporaryPassword),
            'status' => (string) $officer->status === 'suspended' ? 'suspended' : 'off_duty',
            'must_change_password' => true,
            'temporary_password_expires_at' => $expiresAt,
            'activated_at' => $officer->activated_at ?? now(),
            'invitation_token_hash' => null,
            'invitation_expires_at' => null,
        ])->save();

        OfficerSession::query()->where('officer_id', $officer->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $this->audit->record($actor, 'officer.password_reset', 'officer', $officer->id, 'Reset password for '.$officer->service_number, 'warning', [], $request);

        return [
            'officer' => $this->auth->officerContract($officer->fresh()),
            'temporaryPassword' => $temporaryPassword,
            'expiresAt' => $expiresAt->toIso8601String(),
            'mustChangePassword' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activate(string $serviceNumber, string $token, string $password, Request $request): array
    {
        $officer = Officer::query()->where('service_number', $serviceNumber)->first();

        if (! $officer || (string) $officer->status !== 'pending_activation' || ! $officer->invitation_token_hash) {
            throw NpisApiException::make(422, 'INVALID_INVITATION', 'This activation link is invalid or has already been used.');
        }

        if ($officer->invitation_expires_at?->isPast()) {
            throw NpisApiException::make(410, 'INVITATION_EXPIRED', 'This activation link has expired. Ask your commander to resend it.');
        }

        if (! hash_equals($officer->invitation_token_hash, hash('sha256', $token))) {
            throw NpisApiException::make(422, 'INVALID_INVITATION', 'This activation link is invalid or has already been used.');
        }

        $officer->forceFill([
            'password_hash' => Hash::make($password),
            'status' => 'off_duty',
            'activated_at' => now(),
            'invited_at' => $officer->invited_at,
            'invitation_token_hash' => null,
            'invitation_expires_at' => null,
            'must_change_password' => false,
            'temporary_password_expires_at' => null,
            'password_changed_at' => now(),
        ])->save();

        $this->audit->record($officer, 'officer.activated', 'officer', $officer->id, 'Activated officer account '.$officer->service_number, 'done', [], $request);

        return $this->auth->issueSession($officer->fresh(), $request);
    }

    /**
     * @return array<string, mixed>
     */
    private function provisioningResponse(Officer $officer, ?string $rawToken, ?string $temporaryPassword): array
    {
        return [
            'officer' => $this->auth->officerContract($officer->fresh()),
            'invitation' => $rawToken ? [
                'activationToken' => $rawToken,
                'expiresAt' => $officer->invitation_expires_at?->toIso8601String(),
                'delivery' => 'manual',
                'note' => 'Send this token to the officer through a secure channel.',
            ] : null,
            'temporaryPassword' => $temporaryPassword,
            'temporaryPasswordExpiresAt' => $officer->temporary_password_expires_at?->toIso8601String(),
            'mustChangePassword' => $officer->must_change_password,
        ];
    }

    private function temporaryPassword(): string
    {
        return 'Ntem-'.Str::upper(Str::random(4)).'-'.random_int(1000, 9999);
    }
}
