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
 * @property OrganizerRole $role
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
        'role',
        'oidc_subject',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizerRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        $role = $this->role instanceof OrganizerRole ? $this->role->value : (string) $this->role;

        return config("npis.organizer.roles.{$role}.permissions", []);
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
