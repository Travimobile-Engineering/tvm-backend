<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $registration_id
 * @property string $certificate_number
 * @property string|null $file_path
 * @property string|null $file_url
 * @property string|null $file_public_id
 * @property Carbon $issued_at
 * @property int|null $issued_by_admin_id
 * @property string $delivery_channel
 * @property string $delivery_status
 * @property int $delivery_attempts
 * @property Carbon|null $last_attempt_at
 * @property string|null $last_error
 * @property-read Registration $registration
 * @property-read Collection<int, CertificateDelivery> $deliveries
 */
class Certificate extends Model
{
    protected $table = 'npis_certificates';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'registration_id',
        'certificate_number',
        'file_path',
        'file_url',
        'file_public_id',
        'issued_at',
        'issued_by_admin_id',
        'delivery_channel',
        'delivery_status',
        'delivery_attempts',
        'last_attempt_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'delivery_attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * @return HasMany<CertificateDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(CertificateDelivery::class, 'certificate_id');
    }
}
