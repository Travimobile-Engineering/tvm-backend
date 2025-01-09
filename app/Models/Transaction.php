<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $guarded = [];

    public static function boot(){
        parent::boot();

        static::creating(function($model){
            $model->user_id = Auth::id();
        });
    }
}
