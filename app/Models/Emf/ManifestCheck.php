<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $manifest_id
 * @property string $type
 * @property string $status
 * @property string|null $result
 * @property string|null $performed_by
 * @property Carbon $performed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manifest|null $manifest
 * @property-read Officer|null $officer
 */
class ManifestCheck extends Model
{
    protected $table = 'emf_manifest_checks';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'manifest_id', 'type', 'status', 'result', 'performed_by', 'performed_at'];

    protected function casts(): array
    {
        return ['performed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Manifest, $this>
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class, 'manifest_id');
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'performed_by');
    }
}
