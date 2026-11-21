<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $officer_id
 * @property string $request_type
 * @property string $subject
 * @property string $message
 * @property string|null $app_version
 * @property array<string, mixed>|null $device_info
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 */
class SupportRequest extends Model
{
    protected $table = 'emf_support_requests';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'officer_id', 'request_type', 'subject', 'message', 'app_version', 'device_info', 'status'];

    protected function casts(): array
    {
        return ['device_info' => 'array'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
