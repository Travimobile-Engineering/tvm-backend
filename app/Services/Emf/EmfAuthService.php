<?php

namespace App\Services\Emf;

use App\Exceptions\NpisApiException;
use App\Models\Emf\AccessGrant;
use App\Models\Emf\Officer;
use App\Models\Emf\OfficerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmfAuthService
{
    public function __construct(private readonly EmfAuditService $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function login(string $serviceNumber, string $password, Request $request): array
    {
        $officer = Officer::query()->where('service_number', $serviceNumber)->first();

        if (! $officer || ! Hash::check($password, $officer->password_hash)) {
            $this->audit->record(null, 'officer.login_failed', 'officer', null, 'Failed sign-in', 'blocked', [
                'serviceNumber' => $serviceNumber,
            ], $request);

            throw NpisApiException::make(401, 'UNAUTHENTICATED', 'Invalid service number or password.');
        }

        if ($officer->status === 'suspended') {
            throw NpisApiException::make(403, 'FORBIDDEN', 'This account is suspended. Contact the state commander.');
        }

        $rawToken = Str::random(64);
        $expiresAt = now()->addSeconds((int) config('emf.session_ttl', 28800));

        OfficerSession::create([
            'id' => 'ems_'.Str::lower((string) Str::ulid()),
            'officer_id' => $officer->id,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => $expiresAt,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $officer->forceFill([
            'last_active_at' => now(),
            'status' => (string) $officer->status === 'suspended' ? 'suspended' : 'on_duty',
        ])->save();

        $this->audit->record($officer, 'officer.signed_in', 'officer', $officer->id, 'Officer signed in', 'done', [], $request);

        return [
            'token' => $rawToken,
            'expiresAt' => $expiresAt->toIso8601String(),
            'officer' => $this->officerContract($officer),
        ];
    }

    public function logout(Officer $officer, Request $request): void
    {
        $token = (string) $request->bearerToken();

        OfficerSession::query()
            ->where('officer_id', $officer->id)
            ->where('token_hash', hash('sha256', $token))
            ->update(['revoked_at' => now()]);

        $this->audit->record($officer, 'officer.signed_out', 'officer', $officer->id, 'Officer signed out', 'done', [], $request);
    }

    /**
     * @return array<string, mixed>
     */
    public function officerContract(Officer $officer): array
    {
        $officer->loadMissing(['dutyPoint', 'state', 'lga']);

        return [
            'officerId' => $officer->id,
            'serviceNumber' => $officer->service_number,
            'rank' => $officer->rank,
            'fullName' => $officer->full_name,
            'email' => $officer->email,
            'phone' => $officer->phone,
            'unit' => $officer->unit,
            'role' => $officer->role,
            'roleLabel' => (string) config("emf.roles.{$officer->role}.label", $officer->role),
            'status' => $officer->status,
            'language' => $officer->language,
            'photoUrl' => $officer->photo_url,
            'division' => $officer->division,
            'opsRoom' => $officer->ops_room,
            'reportsTo' => $officer->reports_to,
            'twoFactorEnabled' => $officer->two_factor_enabled,
            'biometricEnabled' => $officer->biometric_enabled,
            'theme' => $officer->theme,
            'stateCode' => $officer->state_code,
            'stateName' => $officer->state?->name,
            'lgaId' => $officer->lga_id,
            'lgaName' => $officer->lga?->name,
            'dutyPoint' => $officer->dutyPoint ? [
                'dutyPointId' => $officer->dutyPoint->id,
                'name' => $officer->dutyPoint->name,
                'type' => $officer->dutyPoint->type,
            ] : null,
            'checksToday' => $officer->checks_today,
            'activeGrants' => AccessGrant::query()
                ->where('officer_id', $officer->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->count(),
            'permissions' => $officer->permissions(),
            'lastActiveAt' => $officer->last_active_at?->toIso8601String(),
        ];
    }
}
