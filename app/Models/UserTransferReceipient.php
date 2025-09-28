<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTransferReceipient extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'recipient_code',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
