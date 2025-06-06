<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountTransfer extends Model
{
    protected $fillable = [
        'account_id',
        'amount',
        'reference',
        'transfer_code',
        'response',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array'
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
