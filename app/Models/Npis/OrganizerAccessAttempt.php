<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ip
 * @property bool $succeeded
 * @property Carbon $attempted_at
 */
class OrganizerAccessAttempt extends Model
{
    protected $table = 'npis_organizer_access_attempts';

    protected $fillable = ['ip', 'succeeded', 'attempted_at'];

    protected function casts(): array
    {
        return [
            'succeeded' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }
}
