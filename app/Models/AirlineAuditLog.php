<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $airline_id
 * @property int|null $api_key_id
 * @property string $event
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $meta
 * @property Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AirlineAuditLog forEvent(string $event)
 */
class AirlineAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'airline_id',
        'api_key_id',
        'event',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public const EVENT_LOGIN_SUCCESS = 'login.success';

    public const EVENT_LOGIN_FAILED = 'login.failed';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_SIGNUP = 'signup';

    public const EVENT_KEY_GENERATED = 'key.generated';

    public const EVENT_KEY_REVOKED = 'key.revoked';

    public const EVENT_KEY_ROTATED = 'key.rotated';

    public const EVENT_ENV_SWITCHED = 'environment.switched';

    public const EVENT_PASSWORD_CHANGED = 'password.changed';

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(AirlineApiKey::class);
    }
}
