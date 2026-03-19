<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $slug
 * @property string $code
 * @property string $currency
 */
class Bank extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code',
        'currency',
    ];
}
