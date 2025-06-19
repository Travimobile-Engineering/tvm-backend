<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWithdrawLog extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'transfer_code',
        'status',
        'data',
        'ip_address',
        'device',
        'previous_balance',
        'new_balance',
        'reference',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array'
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
