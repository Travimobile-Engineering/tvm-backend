<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'additional_data'
    ];

    public function casts(){
        return [
            'additional_data' => 'array'
        ];
    }

    public static function boot(){
        parent::boot();

        static::creating(function($model){
            $model->user_id = Auth::id();
        });
    }
}
