<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeesShareFormula extends Model
{
    protected $table = 'fees_share_formula';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'percentage'
    ];
}
