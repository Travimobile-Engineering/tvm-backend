<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = [
            [
                'name' => 'Coaster Bus',
                'slug' => 'coaster-bus',
                'rows' => 10,
                'columns' => 4,
            ],
            [
                'name' => 'Mini Van',
                'slug' => 'mini-van',
                'rows' => 3,
                'columns' => 3,
            ],
            [
                'name' => 'Passenger Van',
                'slug' => 'passenger-van',
                'rows' => 4,
                'columns' => 4,
            ],
            [
                'name' => 'Mini Bus',
                'slug' => 'mini-bus',
                'rows' => 6,
                'columns' => 4,
            ],
            [
                'name' => 'City Bus',
                'slug' => 'city-bus',
                'rows' => 12,
                'columns' => 4,
            ],
            [
                'name' => 'Hiace Van',
                'slug' => 'hiace-van',
                'rows' => 4,
                'columns' => 3,
            ],
            [
                'name' => 'Transit Mini Bus',
                'slug' => 'transit-mini-bus',
                'rows' => 5,
                'columns' => 4,
            ],
            [
                'name' => 'E-series Passenger Van',
                'slug' => 'e-series-passenger-van',
                'rows' => 5,
                'columns' => 4,
            ],
            [
                'name' => 'Sprinter Van',
                'slug' => 'sprinter-van',
                'rows' => 4,
                'columns' => 4,
            ],
            [
                'name' => 'Citaro City Bus',
                'slug' => 'citaro-city-bus',
                'rows' => 15,
                'columns' => 4,
            ],
            [
                'name' => 'NV350 Urvan',
                'slug' => 'nv350-urvan',
                'rows' => 4,
                'columns' => 3,
            ],
            [
                'name' => 'Caravan Van',
                'slug' => 'caravan-van',
                'rows' => 3,
                'columns' => 3,
            ],
            [
                'name' => 'Transporter Van',
                'slug' => 'transporter-van',
                'rows' => 4,
                'columns' => 3,
            ],
            [
                'name' => 'Crafter Minibus',
                'slug' => 'crafter-minibus',
                'rows' => 7,
                'columns' => 4,
            ],
            [
                'name' => 'County Bus',
                'slug' => 'county-bus',
                'rows' => 8,
                'columns' => 4,
            ],
            [
                'name' => 'Staria Van',
                'slug' => 'staria-van',
                'rows' => 4,
                'columns' => 4,
            ],
            [
                'name' => 'Express Passenger Van',
                'slug' => 'express-passenger-van',
                'rows' => 5,
                'columns' => 4,
            ],
            [
                'name' => 'Starcraft Shuttle Bus',
                'slug' => 'starcraft-shuttle-bus',
                'rows' => 9,
                'columns' => 4,
            ],
        ];

        foreach ($vehicleTypes as $vehicleType) {
            DB::table('vehicle_types')->insert(array_merge($vehicleType, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
