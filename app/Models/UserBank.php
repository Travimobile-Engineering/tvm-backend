<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBank extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
        'recipient_code',
        'data',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
