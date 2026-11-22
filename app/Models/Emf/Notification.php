<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $officer_id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property bool $needs_action
 * @property string $status
 * @property string|null $link
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Officer|null $officer
 */
class Notification extends Model
{
    protected $table = 'emf_notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'officer_id', 'type', 'title', 'body', 'needs_action', 'status', 'link', 'read_at'];

    protected function casts(): array
    {
        return ['needs_action' => 'boolean', 'read_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }
}
