<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('route_regions')->insert([
            ['name' => 'Rivers'],
            ['name' => 'Lagos'],
            ['name' => 'Abuja'],
            ['name' => 'Kaduna'],
            ['name' => 'Enugu'],
            ['name' => 'Kanu'],
            ['name' => 'Ogun'],
            ['name' => 'Delta'],
            ['name' => 'Ekiti'],
        ]);

        DB::table('route_subregions')->insert([
            ['state_id' => 33, 'name' => 'Waterline'],
            ['state_id' => 33, 'name' => 'Rumuokoro'],
            ['state_id' => 33, 'name' => 'Rumuola'],
            ['state_id' => 33, 'name' => 'Abali Park'],
            ['state_id' => 33, 'name' => 'Choba'],
            ['state_id' => 33, 'name' => 'Emuoha'],
            ['state_id' => 33, 'name' => 'Eleme'],
            ['state_id' => 33, 'name' => 'Andoni'],
            ['state_id' => 33, 'name' => 'Bonny'],
            ['state_id' => 33, 'name' => 'Ahoada East'],
            ['state_id' => 33, 'name' => 'Ahoada West'],

            ['state_id' => 25, 'name' => 'Lekki'],
            ['state_id' => 25, 'name' => 'Maryland'],
            ['state_id' => 25, 'name' => 'Ikeja'],
            ['state_id' => 25, 'name' => 'Ikorodu'],
            ['state_id' => 25, 'name' => 'Apapa'],

            ['state_id' => 15, 'name' => 'Garki'],
            ['state_id' => 15, 'name' => 'Wuse'],
            ['state_id' => 15, 'name' => 'Asokoro'],
            ['state_id' => 15, 'name' => 'Maitama'],
            ['state_id' => 15, 'name' => 'Nyanya'],

        ]);

        DB::table('covered_routes')->insert([

            ['from_region_id' => 33, 'from_subregion_id' => 1, 'to_region_id' => 25, 'to_subregion_id' => 4],
            ['from_region_id' => 33, 'from_subregion_id' => 2, 'to_region_id' => 25, 'to_subregion_id' => 3],
            ['from_region_id' => 33, 'from_subregion_id' => 3, 'to_region_id' => 25, 'to_subregion_id' => 2],
            ['from_region_id' => 33, 'from_subregion_id' => 4, 'to_region_id' => 25, 'to_subregion_id' => 1],
            ['from_region_id' => 25, 'from_subregion_id' => 1, 'to_region_id' => 15, 'to_subregion_id' => 4],
            ['from_region_id' => 25, 'from_subregion_id' => 2, 'to_region_id' => 15, 'to_subregion_id' => 3],
            ['from_region_id' => 25, 'from_subregion_id' => 3, 'to_region_id' => 15, 'to_subregion_id' => 2],
            ['from_region_id' => 25, 'from_subregion_id' => 4, 'to_region_id' => 15, 'to_subregion_id' => 1],
            ['from_region_id' => 15, 'from_subregion_id' => 1, 'to_region_id' => 33, 'to_subregion_id' => 4],
            ['from_region_id' => 15, 'from_subregion_id' => 2, 'to_region_id' => 33, 'to_subregion_id' => 3],
            ['from_region_id' => 15, 'from_subregion_id' => 3, 'to_region_id' => 33, 'to_subregion_id' => 2],
            ['from_region_id' => 15, 'from_subregion_id' => 4, 'to_region_id' => 33, 'to_subregion_id' => 1],
            ['from_region_id' => 33, 'from_subregion_id' => 1, 'to_region_id' => 25, 'to_subregion_id' => 4],
            ['from_region_id' => 33, 'from_subregion_id' => 2, 'to_region_id' => 15, 'to_subregion_id' => 3],
            ['from_region_id' => 33, 'from_subregion_id' => 3, 'to_region_id' => 15, 'to_subregion_id' => 2],
            ['from_region_id' => 33, 'from_subregion_id' => 4, 'to_region_id' => 33, 'to_subregion_id' => 1],

        ]);
    }
}
