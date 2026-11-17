<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $table = 'npis_hotels';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'name', 'room_types', 'is_active'];

    protected function casts(): array
    {
        return [
            'room_types' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
