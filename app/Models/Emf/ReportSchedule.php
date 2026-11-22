<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $report_type
 * @property array<string, mixed>|null $filters
 * @property string $frequency
 * @property array<int, string>|null $recipients
 * @property Carbon $next_run_at
 * @property bool $active
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ReportSchedule extends Model
{
    protected $table = 'emf_report_schedules';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'report_type', 'filters', 'frequency', 'recipients', 'next_run_at', 'active', 'created_by'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'recipients' => 'array', 'next_run_at' => 'datetime', 'active' => 'boolean'];
    }
}
