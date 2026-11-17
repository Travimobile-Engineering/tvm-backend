<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

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
