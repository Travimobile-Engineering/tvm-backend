<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $service_number
 * @property string $rank
 * @property string $full_name
 * @property string $email
 * @property string $phone
 * @property string $unit
 * @property string $state_code
 * @property string|null $lga_id
 * @property string|null $duty_point_id
 * @property string $role
 * @property string $status
 * @property string $language
 * @property string $password_hash
 * @property string|null $watchlist_edit_code_hash
 * @property Carbon|null $last_active_at
 * @property int $checks_today
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DutyPoint|null $dutyPoint
 * @property-read Lga|null $lga
 * @property-read State|null $state
 * @property-read Collection<int, OfficerSession> $sessions
 * @property-read Collection<int, AccessGrant> $grants
 * @property-read Collection<int, Device> $devices
 */
class Officer extends Model
{
    protected $table = 'emf_officers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'service_number', 'rank', 'full_name', 'email', 'phone', 'unit', 'state_code', 'lga_id', 'duty_point_id', 'role', 'status', 'language', 'password_hash', 'watchlist_edit_code_hash', 'last_active_at', 'checks_today'];

    protected function casts(): array
    {
        return ['last_active_at' => 'datetime', 'checks_today' => 'integer'];
    }

    /**
     * @return BelongsTo<DutyPoint, $this>
     */
    public function dutyPoint(): BelongsTo
    {
        return $this->belongsTo(DutyPoint::class, 'duty_point_id');
    }

    /**
     * @return BelongsTo<Lga, $this>
     */
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class, 'lga_id');
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_code');
    }

    /**
     * @return HasMany<OfficerSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(OfficerSession::class, 'officer_id');
    }

    /**
     * @return HasMany<AccessGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(AccessGrant::class, 'officer_id');
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'officer_id');
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        $role = $this->role;

        return (array) config("emf.roles.{$role}.permissions", []);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
