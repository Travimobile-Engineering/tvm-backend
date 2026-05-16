<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NtemEvent extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'organization',
        'job_title',
        'state',
        'referral_source',
        'dietary_preference',
    ];
}
