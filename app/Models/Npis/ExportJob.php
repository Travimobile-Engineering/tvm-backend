<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int|null $admin_user_id
 * @property array<string, mixed> $filters
 * @property list<string> $fields
 * @property string $status
 * @property int|null $row_count
 * @property string|null $file_path
 * @property string|null $file_url
 * @property string|null $file_public_id
 * @property string|null $error
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 */
class ExportJob extends Model
{
    protected $table = 'npis_export_jobs';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'admin_user_id',
        'filters',
        'fields',
        'status',
        'row_count',
        'file_path',
        'file_url',
        'file_public_id',
        'error',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'fields' => 'array',
            'row_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
