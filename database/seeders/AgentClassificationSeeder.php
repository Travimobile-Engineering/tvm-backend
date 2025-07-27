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
            ['level' => 'A', 'amount' => 1000],
            ['level' => 'B', 'amount' => 2000],
            ['level' => 'C', 'amount' => 3000],
            ['level' => 'D', 'amount' => 4000],
        ];

        DB::table('agent_classifications')->insert($data);
    }
}
