<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOpening extends Model
{
    protected $fillable = [
        'title',
        'type',
        'deadline',
        'summary',
        'responsibilities',
        'requirement',
        'offer',
    ];

    protected function casts(){
        return [
            'responsibilities' => 'array',
            'requirement' => 'array',
            'offer' => 'array',
        ];
    }
}
