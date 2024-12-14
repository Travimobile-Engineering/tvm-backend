<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicle_types')->insert([
            ['name' => 'Coaster Bus'],
            ['name' => 'Mini Van'],
            ['name' => 'Passenger Van'],
            ['name' => 'Mini Bus'],
            ['name' => 'City Bus'],
            ['name' => 'Hiace Van'],
            ['name' => 'Transit Mini Bus'],
            ['name' => 'E-series Passenger Van'],
            ['name' => 'Sprinter Van'],
            ['name' => 'Citaro City Bus'],
            ['name' => 'NV350 Urvan'],
            ['name' => 'Caravan Van'],
            ['name' => 'Transporter Van'],
            ['name' => 'Crafter Minibus'],
            ['name' => 'County Bus'],
            ['name' => 'Staria Van'],
            ['name' => 'Express Passenger Van'],
            ['name' => 'Starcraft Shuttle Bus']
        ]);
    }
}
