<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransitCompanyUnion extends Model
{
    use HasFactory;

    protected $table = 'transit_company_unions';

    protected $fillable = [
        'name',
    ];
}
