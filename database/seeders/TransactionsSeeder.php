<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transactions')->insert([
            ['user_id' => 1, 'title' => 'Wallet top up', 'amount' => 20000, 'type' => 'CR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Bus ticket purchase', 'amount' => 12000, 'type' => 'DR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Bus ticket purchase', 'amount' => 6000, 'type' => 'DR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Wallet top up', 'amount' => 35000, 'type' => 'CR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Bus ticket purchase', 'amount' => 18000, 'type' => 'DR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Wallet top up', 'amount' => 10000, 'type' => 'CR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Wallet top up', 'amount' => 15000, 'type' => 'CR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Bus ticket purchase', 'amount' => 22000, 'type' => 'DR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Bus ticket purchase', 'amount' => 7000, 'type' => 'DR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
            ['user_id' => 1, 'title' => 'Wallet top up', 'amount' => 11000, 'type' => 'CR', 'created_at' => date('Y-m-d H:i:s', strtotime('now - '.rand(3600, 259200).' seconds'))],
        ]);
    }
}
