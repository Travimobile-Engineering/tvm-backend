<?php

namespace Database\Seeders;

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
            ['user_id' => 1, 'uuid' => 'fcdCO6nVd43FIWX', 'vehicle_id' => '2', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '1', 'departure' => '1', 'destination' => '12', 'price' => '13000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 11 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 13 hours'))],
            ['user_id' => 1, 'uuid' => 'vcrgCO6nVlk3FIWX', 'vehicle_id' => '5', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '3', 'departure' => '5', 'destination' => '18', 'price' => '26000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 37 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 64 hours'))],
            ['user_id' => 1, 'uuid' => 'jyCO6nVasc3FIWX', 'vehicle_id' => '6', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '2', 'departure' => '10', 'destination' => '16', 'price' => '28000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 159 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 164 hours'))],
            ['user_id' => 1, 'uuid' => 'y6tCO6nVJd3FIWX', 'vehicle_id' => '3', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '4', 'departure' => '2', 'destination' => '19', 'price' => '40000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 732 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 737 hours'))],
            ['user_id' => 1, 'uuid' => 'ergCO6nVJkjsdjFIWX', 'vehicle_id' => '8', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '5', 'departure' => '6', 'destination' => '19', 'price' => '16000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 609 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 614 hours'))],
            ['user_id' => 1, 'uuid' => 'rgCO6nVJd3FsdfasX', 'vehicle_id' => '7', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '7', 'departure' => '9', 'destination' => '17', 'price' => '32000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 583 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 587 hours'))],
            ['user_id' => 1, 'uuid' => 'sghCO6nVJd3FIsdsd', 'vehicle_id' => '5', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '6', 'departure' => '3', 'destination' => '14', 'price' => '62000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 398 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 403 hours'))],
            ['user_id' => 1, 'uuid' => 'hdCO6nVJddsdsIWX', 'vehicle_id' => '10', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '8', 'departure' => '8', 'destination' => '17', 'price' => '45000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 641 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 643 hours'))],
            ['user_id' => 1, 'uuid' => 'ufdCO6nVJdasiujkdX', 'vehicle_id' => '2', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '9', 'departure' => '7', 'destination' => '21', 'price' => '17000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 733 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 737 hours'))],
            ['user_id' => 1, 'uuid' => 'ubfCO6nVJd3FsdafaIWX', 'vehicle_id' => '9', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '1', 'departure' => '4', 'destination' => '20', 'price' => '9000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 420 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 428 hours'))],
            ['user_id' => 1, 'uuid' => 'drtCO6nVJd3F34erdIWX', 'vehicle_id' => '5', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '10', 'departure' => '9', 'destination' => '16', 'price' => '4500', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 306 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 310 hours'))],
            ['user_id' => 1, 'uuid' => 'bgyCO6nVJjnyuh3d3FIWX', 'vehicle_id' => '3', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '2', 'departure' => '6', 'destination' => '21', 'price' => '6500', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 88 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 92 hours'))],
            ['user_id' => 1, 'uuid' => 'jttCO6udhnVJd3FIWX', 'vehicle_id' => '9', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '4', 'departure' => '3', 'destination' => '13', 'price' => '12500', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 300 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 304 hours'))],
            ['user_id' => 1, 'uuid' => 'dtjhCO6nVJsndessd3FIWX', 'vehicle_id' => '2', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '3', 'departure' => '5', 'destination' => '18', 'price' => '23000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 181 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 185 hours'))],
            ['user_id' => 1, 'uuid' => 'jhCO6nsdmVJd3FIWX', 'vehicle_id' => '7', 'bus_type' => 'car', 'bus_stops' => json_encode(['Mon - 08:00am']), 'transit_company_id' => '5', 'departure' => '2', 'destination' => '12', 'price' => '11000', 'departure_at' => date('Y-m-d H:i:s', strtotime('now + 80 hours')), 'estimated_arrival_at' => date('Y-m-d H:i:s', strtotime('now + 85 hours'))],
        ]);
    }
}
