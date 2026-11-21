<?php

namespace App\Models\Emf;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $requested_by
 * @property string $report_type
 * @property array<string, mixed>|null $filters
 * @property string $format
 * @property string $status
 * @property string|null $file_url
 * @property int|null $row_count
 * @property bool $watermark
 * @property bool $requires_2fa
 * @property Carbon|null $verified_at
 * @property string|null $error
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ExportJob extends Model
{
    protected $table = 'emf_export_jobs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'requested_by', 'report_type', 'filters', 'format', 'status', 'file_url', 'row_count', 'watermark', 'requires_2fa', 'verified_at', 'error', 'expires_at'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'row_count' => 'integer', 'watermark' => 'boolean', 'requires_2fa' => 'boolean', 'verified_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
