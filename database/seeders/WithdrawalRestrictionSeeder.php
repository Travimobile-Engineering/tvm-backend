<?php

namespace Database\Seeders;

use App\Enum\UserType;
use Illuminate\Database\Seeder;
use App\Models\WithdrawalRestriction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class WithdrawalRestrictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WithdrawalRestriction::create([
            'is_active' => true,
            'user_types' => [UserType::AGENT->value],
            'min_balance' => 5000,
            'complete_block' => true,
            'message' => 'Sorry, withdrawals are temporarily unavailable for agents.'
        ]);

        WithdrawalRestriction::create([
            'is_active' => false,
            'user_types' => [UserType::DRIVER->value],
            'min_balance' => 0,
            'complete_block' => false,
            'message' => 'Sorry, withdrawals are temporarily unavailable for drivers'
        ]);
    }
}
