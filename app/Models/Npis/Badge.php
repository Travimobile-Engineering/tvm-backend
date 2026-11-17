<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $registration_id
 * @property string|null $category
 * @property string $source
 * @property string|null $photo_path
 * @property string|null $photo_url
 * @property string|null $photo_public_id
 * @property string|null $photo_source
 * @property Carbon|null $photo_uploaded_at
 * @property-read Registration $registration
 * @property-read ProtocolCategory|null $protocolCategory
 */
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

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * @return BelongsTo<ProtocolCategory, $this>
     */
    public function protocolCategory(): BelongsTo
    {
        return $this->belongsTo(ProtocolCategory::class, 'category');
    }
}
