<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
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
        'type',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'account_name' => EncryptedAttribute::class,
            'account_number' => EncryptedAttribute::class,
        ];
    }

    public function accountTransfers()
    {
        return $this->hasMany(AccountTransfer::class);
    }
}
