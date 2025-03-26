<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'read',
        'additional_data'
    ];

    public function casts()
    {
        return [
            'additional_data' => 'array'
        ];
    }
}
