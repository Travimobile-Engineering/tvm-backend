<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Zone::count() === 0) {
            DB::table('zones')->insert([
                ['name' => 'north-east'],
                ['name' => 'north-west'],
                ['name' => 'north-central'],
                ['name' => 'south-west'],
                ['name' => 'south-east'],
                ['name' => 'south-south'],
            ]);
        }
    }
}
