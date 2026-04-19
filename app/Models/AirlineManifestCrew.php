<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirlineManifestCrew extends Model
{
    use HasFactory;

    protected $fillable = ['manifest_id', 'name', 'role'];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }
}
