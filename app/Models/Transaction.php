<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'type',
        'sender_id',
        'receiver_id',
        'txn_reference',
        'status'
    ];

    public function casts(): array
    {
        return [
            'amount' => 'decimal:10,2',
        ];
    }
}
