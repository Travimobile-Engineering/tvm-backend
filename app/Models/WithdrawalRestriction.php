<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRestriction extends Model
{
    protected $fillable = [
        'is_active',
        'user_types',
        'min_balance',
        'message',
        'complete_block',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'user_types' => 'array',
            'min_balance' => 'decimal:2',
            'complete_block' => 'boolean',
        ];
    }

    public function appliesToUserType(string $userType): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return in_array($userType, $this->user_types ?? []);
    }

    public static function getActiveRestrictions()
    {
        return static::where('is_active', true)->get();
    }

    public static function checkRestriction(string $userType, float $currentBalance): ?self
    {
        return static::where('is_active', true)
            ->whereJsonContains('user_types', $userType)
            ->where('min_balance', '>', $currentBalance)
            ->first();
    }
}
