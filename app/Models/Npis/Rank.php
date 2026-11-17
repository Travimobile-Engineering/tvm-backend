<?php

namespace App\Models\Npis;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $table = 'npis_ranks';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['code', 'name', 'abbreviation', 'category', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
