<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'fees',
        'recipient_code',
        'data',
        'is_default',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array'
        ];
    }

    public function accountTransfers()
    {
        return $this->hasMany(AccountTransfer::class);
    }
}
