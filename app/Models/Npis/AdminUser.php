<?php

namespace App\Models\Npis;

use App\Enum\Npis\OrganizerRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property OrganizerRole $role
 * @property list<string>|null $extra_permissions
 * @property string|null $oidc_subject
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 */
class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'npis_admin_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'extra_permissions',
        'oidc_subject',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizerRole::class,
            'password' => 'hashed',
            'extra_permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Effective permissions: the role defaults plus any permissions granted to
     * this specific organizer on top of the role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $role = $this->role instanceof OrganizerRole ? $this->role->value : (string) $this->role;
        $rolePermissions = array_values((array) config("npis.organizer.roles.{$role}.permissions", []));
        $extra = is_array($this->extra_permissions) ? $this->extra_permissions : [];

        return array_values(array_unique(array_merge($rolePermissions, $extra)));
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function roleLabel(): string
    {
        $role = $this->role instanceof OrganizerRole ? $this->role->value : (string) $this->role;

        return config("npis.organizer.roles.{$role}.label", $role);
    }
}
