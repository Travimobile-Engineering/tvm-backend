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
 * @property string|null $nin
 * @property string|null $division
 * @property string|null $reports_to
 * @property string|null $ops_room
 * @property string|null $photo_url
 * @property Carbon|null $password_changed_at
 * @property bool $two_factor_enabled
 * @property string|null $two_factor_secret
 * @property bool $biometric_enabled
 * @property string $theme
 * @property array<string, bool>|null $field_notification_preferences
 * @property array<int, string>|null $backup_codes
 * @property string|null $invitation_token_hash
 * @property Carbon|null $invitation_expires_at
 * @property Carbon|null $invited_at
 * @property Carbon|null $activated_at
 * @property bool $must_change_password
 * @property Carbon|null $temporary_password_expires_at
 * @property string|null $created_by
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
 * @property-read Collection<int, FieldCheck> $fieldChecks
 * @property-read Collection<int, SupportRequest> $supportRequests
 * @property-read Officer|null $creator
 */
class Officer extends Model
{
    protected $table = 'emf_officers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'service_number', 'rank', 'full_name', 'email', 'phone', 'unit', 'state_code', 'lga_id', 'duty_point_id', 'role', 'status', 'language', 'password_hash', 'watchlist_edit_code_hash', 'nin', 'division', 'reports_to', 'ops_room', 'photo_url', 'password_changed_at', 'two_factor_enabled', 'two_factor_secret', 'biometric_enabled', 'theme', 'field_notification_preferences', 'backup_codes', 'invitation_token_hash', 'invitation_expires_at', 'invited_at', 'activated_at', 'must_change_password', 'temporary_password_expires_at', 'created_by', 'last_active_at', 'checks_today'];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'biometric_enabled' => 'boolean',
            'field_notification_preferences' => 'array',
            'backup_codes' => 'array',
            'invitation_expires_at' => 'datetime',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'datetime',
            'checks_today' => 'integer',
        ];
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
     * @return HasMany<FieldCheck, $this>
     */
    public function fieldChecks(): HasMany
    {
        return $this->hasMany(FieldCheck::class, 'officer_id');
    }

    /**
     * @return HasMany<SupportRequest, $this>
     */
    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class, 'officer_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
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
