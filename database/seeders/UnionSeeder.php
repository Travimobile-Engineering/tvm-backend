<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UnionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transit_company_unions')->insert([
            ['name' => 'NURTW'],
            ['name' => 'NARTO'],
            ['name' => 'RTEAN'],
        ]);
    }
}
