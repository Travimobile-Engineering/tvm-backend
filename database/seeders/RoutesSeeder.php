<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => 'PHC'],
            ['name' => 'Lagos'],
            ['name' => 'Abuja'],
            ['name' => 'Kaduna'],
            ['name' => 'Enugu'],
            ['name' => 'Kanu'],
            ['name' => 'Ogun'],
            ['name' => 'Rivers'],
            ['name' => 'Delta'],
            ['name' => 'Ekiti']
        ]);
        
        DB::table('route_subregions')->insert([
            ['region_id' => 1, 'name' => 'Waterline'],
            ['region_id' => 1, 'name' => 'Rumuokoro'],
            ['region_id' => 1, 'name' => 'Rumuola'],
            ['region_id' => 1, 'name' => 'Abali Park'],
            ['region_id' => 1, 'name' => 'Choba'],
            
            ['region_id' => 2, 'name' => 'Lekki'],
            ['region_id' => 2, 'name' => 'Maryland'],
            ['region_id' => 2, 'name' => 'Ikeja'],
            ['region_id' => 2, 'name' => 'Ikorodu'],
            ['region_id' => 2, 'name' => 'Apapa'],

            ['region_id' => 3, 'name' => 'Garki'],
            ['region_id' => 3, 'name' => 'Wuse'],
            ['region_id' => 3, 'name' => 'Asokoro'],
            ['region_id' => 3, 'name' => 'Maitama'],
            ['region_id' => 3, 'name' => 'Nyanya'],

            ['region_id' => 8, 'name' => 'Emuoha'],
            ['region_id' => 8, 'name' => 'Eleme'],
            ['region_id' => 8, 'name' => 'Andoni'],
            ['region_id' => 8, 'name' => 'Bonny'],
            ['region_id' => 8, 'name' => 'Ahoada East'],
            ['region_id' => 8, 'name' => 'Ahoada West'],
        ]);


        DB::table('covered_routes')->insert([

            ['from_region_id' => 1, 'from_subregion_id' => 1, 'to_region_id' => 2, 'to_subregion_id' => 4],
            ['from_region_id' => 1, 'from_subregion_id' => 2, 'to_region_id' => 2, 'to_subregion_id' => 3],
            ['from_region_id' => 1, 'from_subregion_id' => 3, 'to_region_id' => 2, 'to_subregion_id' => 2],
            ['from_region_id' => 1, 'from_subregion_id' => 4, 'to_region_id' => 2, 'to_subregion_id' => 1],
            ['from_region_id' => 2, 'from_subregion_id' => 1, 'to_region_id' => 3, 'to_subregion_id' => 4],
            ['from_region_id' => 2, 'from_subregion_id' => 2, 'to_region_id' => 3, 'to_subregion_id' => 3],
            ['from_region_id' => 2, 'from_subregion_id' => 3, 'to_region_id' => 3, 'to_subregion_id' => 2],
            ['from_region_id' => 2, 'from_subregion_id' => 4, 'to_region_id' => 3, 'to_subregion_id' => 1],
            ['from_region_id' => 3, 'from_subregion_id' => 1, 'to_region_id' => 1, 'to_subregion_id' => 4],
            ['from_region_id' => 3, 'from_subregion_id' => 2, 'to_region_id' => 1, 'to_subregion_id' => 3],
            ['from_region_id' => 3, 'from_subregion_id' => 3, 'to_region_id' => 1, 'to_subregion_id' => 2],
            ['from_region_id' => 3, 'from_subregion_id' => 4, 'to_region_id' => 1, 'to_subregion_id' => 1],
            ['from_region_id' => 8, 'from_subregion_id' => 1, 'to_region_id' => 4, 'to_subregion_id' => 4],
            ['from_region_id' => 8, 'from_subregion_id' => 2, 'to_region_id' => 4, 'to_subregion_id' => 3],
            ['from_region_id' => 8, 'from_subregion_id' => 3, 'to_region_id' => 4, 'to_subregion_id' => 2],
            ['from_region_id' => 8, 'from_subregion_id' => 4, 'to_region_id' => 4, 'to_subregion_id' => 1],

        ]);
    }
}
