<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleBrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicle_brands')->insert([
            ['name' => 'Toyota'],
            ['name' => 'Ford'],
            ['name' => 'Honda'],
            ['name' => 'chevrolet'],
            ['name' => 'BMW'],
            ['name' => 'Mecedes-Benz'],
            ['name' => 'Volkswagen'],
            ['name' => 'Hyundai'],
            ['name' => 'Nissan'],
            ['name' => 'Audi'],
        ]);
    }
}
