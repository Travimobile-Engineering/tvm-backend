<?php

namespace App\Services\Npis;

use App\Enum\Npis\OrganizerRole;
use App\Mail\NpisOrganizerCredentialsMail;
use App\Models\Npis\AdminUser;
use App\Support\NpisOrganizerPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrganizerManagementService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'roles' => NpisOrganizerPermissions::roles(),
            'permissions' => NpisOrganizerPermissions::catalog(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(AdminUser $actor, array $payload): AdminUser
    {
        $role = OrganizerRole::from((string) $payload['role']);
        $rolePermissions = array_values((array) config("npis.organizer.roles.{$role->value}.permissions", []));
        $requested = array_values(array_unique((array) ($payload['additionalPermissions'] ?? [])));
        $additional = array_values(array_diff($requested, $rolePermissions));

        return DB::transaction(function () use ($actor, $payload, $role, $additional): AdminUser {
            $organizer = AdminUser::create([
                'name' => (string) $payload['name'],
                'email' => (string) $payload['email'],
                'role' => $role,
                'password' => (string) $payload['password'],
                'extra_permissions' => $additional === [] ? null : $additional,
                'is_active' => true,
            ]);

            $this->audit->record(
                $actor,
                'organizer.created',
                'admin_user',
                (string) $organizer->id,
                "Created organizer {$organizer->name} ({$organizer->roleLabel()})",
                ['role' => $role->value, 'additionalPermissions' => $additional],
                request(),
            );

            return $organizer;
        });
    }

    public function sendCredentials(AdminUser $organizer, string $password): void
    {
        Mail::to($organizer->email)->queue(new NpisOrganizerCredentialsMail(
            name: $organizer->name,
            email: $organizer->email,
            password: $password,
            roleLabel: $organizer->roleLabel(),
            loginUrl: (string) config('npis.organizer.login_url'),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function contract(AdminUser $organizer): array
    {
        return [
            'userId' => 'usr_'.$organizer->id,
            'name' => $organizer->name,
            'email' => $organizer->email,
            'role' => $organizer->role->value,
            'roleLabel' => $organizer->roleLabel(),
            'permissions' => $organizer->permissions(),
            'additionalPermissions' => $organizer->extra_permissions ?? [],
            'isActive' => (bool) $organizer->is_active,
            'createdAt' => $organizer->created_at?->toIso8601String(),
        ];
    }
}
