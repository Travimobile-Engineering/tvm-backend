<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('trips')->insert([
            ['trip_id' => 'fcdCO6nVd43FIWX', 'vehicle_id' => '2', 'transit_company_id' => '1', 'from_subregion' => '1', 'to_subregion' => '8', 'price' => '13000', 'departure_at' => '2024-12-1 11:00:00', 'estimated_arrival_at' => '2024-12-1 13:00:00'],
            ['trip_id' => 'vcrgCO6nVlk3FIWX', 'vehicle_id' => '5', 'transit_company_id' => '3', 'from_subregion' => '5', 'to_subregion' => '7', 'price' => '26000', 'departure_at' => '2024-12-3 13:00:00', 'estimated_arrival_at' => '2024-12-3 16:00:00'],
            ['trip_id' => 'jyCO6nVasc3FIWX', 'vehicle_id' => '6', 'transit_company_id' => '2', 'from_subregion' => '10', 'to_subregion' => '4', 'price' => '28000', 'departure_at' => '2024-12-16 15:00:00', 'estimated_arrival_at' => '2024-12-16 20:00:00'],
            ['trip_id' => 'y6tCO6nVJd3FIWX', 'vehicle_id' => '3', 'transit_company_id' => '4', 'from_subregion' => '2', 'to_subregion' => '8', 'price' => '40000', 'departure_at' => '2024-12-30 12:00:00', 'estimated_arrival_at' => '2024-12-30 17:00:00'],
            ['trip_id' => 'ergCO6nVJkjsdjFIWX', 'vehicle_id' => '8', 'transit_company_id' => '5', 'from_subregion' => '6', 'to_subregion' => '2', 'price' => '16000', 'departure_at' => '2024-12-25 09:00:00', 'estimated_arrival_at' => '2024-12-25 14:00:00'],
            ['trip_id' => 'rgCO6nVJd3FsdfasX', 'vehicle_id' => '7', 'transit_company_id' => '7', 'from_subregion' => '9', 'to_subregion' => '3', 'price' => '32000', 'departure_at' => '2024-12-24 07:00:00', 'estimated_arrival_at' => '2024-12-24 11:00:00'],
            ['trip_id' => 'sghCO6nVJd3FIsdsd', 'vehicle_id' => '5', 'transit_company_id' => '6', 'from_subregion' => '3', 'to_subregion' => '6', 'price' => '62000', 'departure_at' => '2024-12-16 14:00:00', 'estimated_arrival_at' => '2024-12-16 19:00:00'],
            ['trip_id' => 'hdCO6nVJddsdsIWX', 'vehicle_id' => '10', 'transit_company_id' => '8', 'from_subregion' => '8', 'to_subregion' => '1', 'price' => '45000', 'departure_at' => '2024-12-26 17:00:00', 'estimated_arrival_at' => '2024-12-26 19:00:00'],
            ['trip_id' => 'ufdCO6nVJdasiujkdX', 'vehicle_id' => '2', 'transit_company_id' => '9', 'from_subregion' => '7', 'to_subregion' => '2', 'price' => '17000', 'departure_at' => '2024-12-30 13:00:00', 'estimated_arrival_at' => '2024-12-30 16:00:00'],
            ['trip_id' => 'ubfCO6nVJd3FsdafaIWX', 'vehicle_id' => '9', 'transit_company_id' => '1', 'from_subregion' => '4', 'to_subregion' => '9', 'price' => '9000', 'departure_at' => '2024-12-17 12:00:00', 'estimated_arrival_at' => '2024-12-17 20:00:00'],
            ['trip_id' => 'drtCO6nVJd3F34erdIWX', 'vehicle_id' => '5', 'transit_company_id' => '10', 'from_subregion' => '9', 'to_subregion' => '5', 'price' => '4500', 'departure_at' => '2024-12-12 18:00:00', 'estimated_arrival_at' => '2024-12-12 22:00:00'],
            ['trip_id' => 'bgyCO6nVJjnyuh3d3FIWX', 'vehicle_id' => '3', 'transit_company_id' => '2', 'from_subregion' => '6', 'to_subregion' => '2', 'price' => '6500', 'departure_at' => '2024-12-3 16:00:00', 'estimated_arrival_at' => '2024-12-3 20:00:00'],
            ['trip_id' => 'jttCO6udhnVJd3FIWX', 'vehicle_id' => '9', 'transit_company_id' => '4', 'from_subregion' => '3', 'to_subregion' => '7', 'price' => '12500', 'departure_at' => '2024-12-6 12:00:00', 'estimated_arrival_at' => '2024-12-6 16:00:00'],
            ['trip_id' => 'dtjhCO6nVJsndessd3FIWX', 'vehicle_id' => '2', 'transit_company_id' => '3', 'from_subregion' => '5', 'to_subregion' => '2', 'price' => '23000', 'departure_at' => '2024-12-7 13:00:00', 'estimated_arrival_at' => '2024-12-7 17:00:00'],
            ['trip_id' => 'jhCO6nsdmVJd3FIWX', 'vehicle_id' => '7', 'transit_company_id' => '5', 'from_subregion' => '2', 'to_subregion' => '9', 'price' => '11000', 'departure_at' => '2024-12-3 08:00:00', 'estimated_arrival_at' => '2024-12-3 13:00:00']
        ]);
    }
}
