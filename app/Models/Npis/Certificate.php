<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(CertificateDelivery::class, 'certificate_id');
    }
}
