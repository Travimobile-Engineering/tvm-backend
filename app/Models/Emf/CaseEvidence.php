<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $case_id
 * @property string $evidence_type
 * @property string $label
 * @property string $file_url
 * @property int|null $file_size
 * @property string|null $uploaded_by
 * @property Carbon $uploaded_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CaseFile|null $case
 */
class CaseEvidence extends Model
{
    protected $table = 'emf_case_evidence';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'case_id', 'evidence_type', 'label', 'file_url', 'file_size', 'uploaded_by', 'uploaded_at', 'metadata'];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'uploaded_at' => 'datetime', 'metadata' => 'array'];
    }

    /**
     * @return BelongsTo<CaseFile, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }
}
