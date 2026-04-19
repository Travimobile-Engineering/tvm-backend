<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirlineManifestPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifest_id',
        'row_number',
        'name',
        'job',
        'company',
        'bag_pcs',
        'bag_wt',
        'pax_wt',
        'total_wt',
        'from_location',
        'to_location',
        'is_special_cargo',
        'special_cargo_type',
    ];

    protected function casts(): array
    {
        return [
            'bag_wt' => 'decimal:2',
            'pax_wt' => 'decimal:2',
            'total_wt' => 'decimal:2',
            'is_special_cargo' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $p) {
            $p->total_wt = $p->bag_wt + $p->pax_wt;
        });
    }

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }
}
