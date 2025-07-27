<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentCommisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['type' => 'Primary', 'amount' => 1000],
            ['type' => 'Secondary', 'amount' => 500],
        ];

        DB::table('agent_commissions')->insert($data);
    }
}
