<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $certificate_id
 * @property string $channel
 * @property string $status
 * @property Carbon $attempted_at
 * @property string|null $provider_message_id
 * @property string|null $error
 * @property-read Certificate $certificate
 */
class CertificateDelivery extends Model
{
    protected $table = 'npis_certificate_deliveries';

    protected $fillable = [
        'certificate_id',
        'channel',
        'status',
        'attempted_at',
        'provider_message_id',
        'error',
    ];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Certificate, $this>
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
}
