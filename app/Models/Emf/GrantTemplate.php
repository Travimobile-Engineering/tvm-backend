<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $scope
 * @property array<int, string>|null $modes
 * @property int $duration_hours
 * @property int $access_count
 * @property string $status
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $creator
 */
class GrantTemplate extends Model
{
    protected $table = 'emf_grant_templates';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'description', 'scope', 'modes', 'duration_hours', 'access_count', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['modes' => 'array', 'duration_hours' => 'integer', 'access_count' => 'integer'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'created_by');
    }
}
