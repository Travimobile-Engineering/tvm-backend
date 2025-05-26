<?php

namespace App\Models;

use App\Facades\UserFacade;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = "documents";

    protected $fillable = [
        'user_id',
        'type',
        'image_url',
        'public_id',
        'number',
        'expiration_date',
        'status',
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

    public function getUserAttribute()
    {
        return UserFacade::find($this->user_id);
    }
}
