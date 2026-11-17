<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    protected $table = 'npis_commands';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'state_code', 'name', 'type', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
