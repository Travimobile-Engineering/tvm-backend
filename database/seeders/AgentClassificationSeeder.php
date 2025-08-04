<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['level' => 'D', 'amount' => 1000, 'reward_amount' => 100],
            ['level' => 'C', 'amount' => 10000, 'reward_amount' => 1000],
            ['level' => 'B', 'amount' => 100000, 'reward_amount' => 10000],
            ['level' => 'A', 'amount' => 1000000, 'reward_amount' => 100000],
        ];

        DB::table('agent_classifications')->insert($data);
    }
}
