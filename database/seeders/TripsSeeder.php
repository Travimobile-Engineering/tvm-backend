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
            ['trip_id' => 'fcdCO6nVd43FIWX', 'vehicle_id' => '2', 'transit_company_id' => '1', 'departure' => '1', 'destination' => '12', 'price' => '13000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 11 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 13 hours'))],
            ['trip_id' => 'vcrgCO6nVlk3FIWX', 'vehicle_id' => '5', 'transit_company_id' => '3', 'departure' => '5', 'destination' => '18', 'price' => '26000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 2 days and 13 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 2 days and 16 hours'))],
            ['trip_id' => 'jyCO6nVasc3FIWX', 'vehicle_id' => '6', 'transit_company_id' => '2', 'departure' => '10', 'destination' => '16', 'price' => '28000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 6 days and 15 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 6 days and 20 hours'))],
            ['trip_id' => 'y6tCO6nVJd3FIWX', 'vehicle_id' => '3', 'transit_company_id' => '4', 'departure' => '2', 'destination' => '19', 'price' => '40000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 30 days and 12 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 30 days and 17 hours'))],
            ['trip_id' => 'ergCO6nVJkjsdjFIWX', 'vehicle_id' => '8', 'transit_company_id' => '5', 'departure' => '6', 'destination' => '19', 'price' => '16000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 25 days and 9 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 25 days and 14 hours'))],
            ['trip_id' => 'rgCO6nVJd3FsdfasX', 'vehicle_id' => '7', 'transit_company_id' => '7', 'departure' => '9', 'destination' => '17', 'price' => '32000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 24 days and 7 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 24 days and 11 hours'))],
            ['trip_id' => 'sghCO6nVJd3FIsdsd', 'vehicle_id' => '5', 'transit_company_id' => '6', 'departure' => '3', 'destination' => '14', 'price' => '62000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 16 days and 14 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 16 days and 19 hours'))],
            ['trip_id' => 'hdCO6nVJddsdsIWX', 'vehicle_id' => '10', 'transit_company_id' => '8', 'departure' => '8', 'destination' => '17', 'price' => '45000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 26 days and 17 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 26 days and 19 hours'))],
            ['trip_id' => 'ufdCO6nVJdasiujkdX', 'vehicle_id' => '2', 'transit_company_id' => '9', 'departure' => '7', 'destination' => '21', 'price' => '17000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 30 days and 13 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 30 days and 16 hours'))],
            ['trip_id' => 'ubfCO6nVJd3FsdafaIWX', 'vehicle_id' => '9', 'transit_company_id' => '1', 'departure' => '4', 'destination' => '20', 'price' => '9000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 17 days and 12 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 17 days and 20 hours'))],
            ['trip_id' => 'drtCO6nVJd3F34erdIWX', 'vehicle_id' => '5', 'transit_company_id' => '10', 'departure' => '9', 'destination' => '16', 'price' => '4500', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 12 days and 18 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 12 days and 22 hours'))],
            ['trip_id' => 'bgyCO6nVJjnyuh3d3FIWX', 'vehicle_id' => '3', 'transit_company_id' => '2', 'departure' => '6', 'destination' => '21', 'price' => '6500', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 3 days and 16 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 3 days and 20 hours'))],
            ['trip_id' => 'jttCO6udhnVJd3FIWX', 'vehicle_id' => '9', 'transit_company_id' => '4', 'departure' => '3', 'destination' => '13', 'price' => '12500', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 12 days and 12 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 12 days and 16 hours'))],
            ['trip_id' => 'dtjhCO6nVJsndessd3FIWX', 'vehicle_id' => '2', 'transit_company_id' => '3', 'departure' => '5', 'destination' => '18', 'price' => '23000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 7 days and 13 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 7 days and 17 hours'))],
            ['trip_id' => 'jhCO6nsdmVJd3FIWX', 'vehicle_id' => '7', 'transit_company_id' => '5', 'departure' => '2', 'destination' => '12', 'price' => '11000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 3 days and 8 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 3 days and 13 hours'))]
        ]);
    }
}
