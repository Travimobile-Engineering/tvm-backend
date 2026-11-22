<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $case_id
 * @property string $action
 * @property string|null $note
 * @property string|null $actor_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CaseFile|null $case
 */
class CaseEvent extends Model
{
    protected $table = 'emf_case_events';

    protected $fillable = ['case_id', 'action', 'note', 'actor_name'];

    protected function casts(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<CaseFile, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }
}
