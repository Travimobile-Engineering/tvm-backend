<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirlineManifestCargo extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_id',
        'row_number',
        'description',
        'pcs',
        'company',
        'weight',
        'from_location',
        'to_location',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }
}
