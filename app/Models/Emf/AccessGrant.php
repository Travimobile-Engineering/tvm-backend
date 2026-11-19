<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $grant_number
 * @property string $officer_id
 * @property string|null $template_id
 * @property string $scope
 * @property array<int, string>|null $modes
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $status
 * @property string|null $created_by
 * @property Carbon|null $revoked_at
 * @property string|null $revoke_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 * @property-read GrantTemplate|null $template
 */
class AccessGrant extends Model
{
    protected $table = 'emf_access_grants';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'grant_number', 'officer_id', 'template_id', 'scope', 'modes', 'starts_at', 'ends_at', 'status', 'created_by', 'revoked_at', 'revoke_reason', 'notes'];

    protected function casts(): array
    {
        return ['modes' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }

    /**
     * @return BelongsTo<GrantTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(GrantTemplate::class, 'template_id');
    }
}
