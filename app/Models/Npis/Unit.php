<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'npis_units';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'command_id', 'name', 'type', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
