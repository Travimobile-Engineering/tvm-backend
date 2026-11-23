<?php

namespace App\Services\Npis;

use App\Enum\MailingEnum;
use App\Enum\Npis\OrganizerRole;
use App\Mail\NpisOrganizerCredentialsMail;
use App\Models\Npis\AdminUser;
use App\Support\NpisOrganizerPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
     * @param  array<string, mixed>  $filters
     * @return array{organizers: Collection<int, AdminUser>, pagination: array{page: int, pageSize: int, totalItems: int, totalPages: int}}
     */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($filters['pageSize'] ?? 25)));
        $sort = (string) ($filters['sort'] ?? '-createdAt');

        $query = AdminUser::query();

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);

            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['isActive'])) {
            $query->where('is_active', filter_var($filters['isActive'], FILTER_VALIDATE_BOOLEAN));
        }

        $total = (clone $query)->count();

        $this->applySort($query, $sort);

        $organizers = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'organizers' => $organizers,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'totalItems' => $total,
                'totalPages' => max(1, (int) ceil($total / $pageSize)),
            ],
        ];
    }

    /**
     * @param  Builder<AdminUser>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        match ($field) {
            'name' => $query->orderBy('name', $direction),
            'lastLoginAt' => $query->orderBy('last_login_at', $direction),
            default => $query->orderBy('created_at', $direction),
        };
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

    public function sendCredentials(AdminUser $organizer, string $password): bool
    {
        $mailing = mailSend(
            MailingEnum::NPIS_ORGANIZER_CREDENTIALS,
            $organizer,
            'Your NPIS 2026 organizer account',
            NpisOrganizerCredentialsMail::class,
            [
                'name' => $organizer->name,
                'email' => $organizer->email,
                'password' => $password,
                'roleLabel' => $organizer->roleLabel(),
                'loginUrl' => (string) config('npis.organizer.login_url'),
            ],
        );

        return $mailing->exists;
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
