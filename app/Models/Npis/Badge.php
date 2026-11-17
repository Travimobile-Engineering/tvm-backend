<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Badge extends Model
{
    protected $table = 'npis_badges';

    protected $fillable = [
        'registration_id',
        'category',
        'source',
        'photo_path',
        'photo_url',
        'photo_public_id',
        'photo_source',
        'photo_uploaded_at',
    ];

    protected function casts(): array
    {
        return ['photo_uploaded_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function protocolCategory(): BelongsTo
    {
        return $this->belongsTo(ProtocolCategory::class, 'category');
    }
}
