<?php

namespace Database\Seeders;

use App\Models\Park;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Park::count() === 0) {
            DB::table('parks')->insert([
                ['name' => 'Park 1', 'route_subregion_id' => 1],
                ['name' => 'Park 2', 'route_subregion_id' => 2],
            ]);
        }
    }
}
