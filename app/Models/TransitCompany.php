<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TransitCompany extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'short_name',
        'reg_no',
        'url',
        'email',
        'country_code',
        'type',
        'state',
        'lga',
        'phone',
        'address',
        'about_details',
        'union_id',
        'union_states_chapter',
        'ev',
        'sv',
        'ver_code',
        'ver_code_expires_at',
        'park',
    ];

    protected $hidden = [
        'ver_code',
        'ver_code_expires_at',
        'ev',
        'sv'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parks(){
        return $this->hasMany(Park::class);
    }
}
