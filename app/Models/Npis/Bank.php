<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'npis_banks';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['code', 'name', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
