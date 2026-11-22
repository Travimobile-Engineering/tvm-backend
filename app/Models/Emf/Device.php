<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $officer_id
 * @property string $device_name
 * @property string|null $platform
 * @property string $status
 * @property Carbon $requested_at
 * @property string|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $last_seen_ip
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 */
class Device extends Model
{
    protected $table = 'emf_devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'officer_id', 'device_name', 'platform', 'status', 'requested_at', 'approved_by', 'approved_at', 'last_seen_ip'];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
