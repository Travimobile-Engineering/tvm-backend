<?php

namespace App\Models;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
