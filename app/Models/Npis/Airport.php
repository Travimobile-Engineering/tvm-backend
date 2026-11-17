<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $table = 'npis_airports';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['code', 'name', 'city', 'state_code', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
