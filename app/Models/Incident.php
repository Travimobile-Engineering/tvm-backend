<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'type',
        'date',
        'time',
        'location',
        'state_id',
        'city',
        'description',
        'media_url',
        'severity_level',
        'persons_of_interest',
        'status',
    ];
}
