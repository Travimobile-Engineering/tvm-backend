<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TransitCompany extends Model
{
    protected $guarded = [
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'ver_code',
        'ver_code_expires_at',
        'ev',
        'sv'
    ];

    
}
