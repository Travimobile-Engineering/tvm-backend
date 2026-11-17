<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
}
