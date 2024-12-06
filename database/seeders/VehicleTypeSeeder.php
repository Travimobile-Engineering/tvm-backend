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
            ['name' => 'Coaster Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Mini Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Passenger Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Mini Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'City Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Hiace Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Transit Mini Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'E-series Passenger Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Sprinter Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Citaro City Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'NV350 Urvan', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Caravan Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Transporter Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Crafter Minibus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'County Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Staria Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Express Passenger Van', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4'],
            ['name' => 'Starcraft Shuttle Bus', 'seat_layout_rows' => '4', 'seat_layout_columns' => '4']
        ]);
    }
}
