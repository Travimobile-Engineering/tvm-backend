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
            ['id' => 1, 'name' => 'PHC'],
            ['id' => 2, 'name' => 'Lagos'],
            ['id' => 3, 'name' => 'Warri'],
            ['id' => 4, 'name' => 'Uyo'],
        ]);
        
        DB::table('route_subregions')->insert([
            ['region_id' => 1, 'name' => 'Waterline'],
            ['region_id' => 1, 'name' => 'Rumuokoro'],
            ['region_id' => 1, 'name' => 'Rumuola'],
            ['region_id' => 1, 'name' => 'Abali Park'],
            ['region_id' => 2, 'name' => 'Lekki'],
            ['region_id' => 2, 'name' => 'Mainland'],
            ['region_id' => 2, 'name' => 'Ikeja'],
            ['region_id' => 2, 'name' => 'Ikorodu'],
            ['region_id' => 3, 'name' => 'Area 1'],
            ['region_id' => 3, 'name' => 'Area 2'],
            ['region_id' => 3, 'name' => 'Area 3'],
            ['region_id' => 3, 'name' => 'Area 4'],
            ['region_id' => 4, 'name' => 'Itam Park'],
            ['region_id' => 4, 'name' => 'Ibom Plaza'],
            ['region_id' => 4, 'name' => 'Aka'],
            ['region_id' => 4, 'name' => 'Perm Site'],
        ]);


        DB::table('covered_routes')->insert([

            ['from_region_id' => 1, 'from_subregion_id' => 1, 'to_region_id' => 2, 'to_subregion_id' => 1],
            ['from_region_id' => 1, 'from_subregion_id' => 2, 'to_region_id' => 2, 'to_subregion_id' => 2],
            ['from_region_id' => 1, 'from_subregion_id' => 3, 'to_region_id' => 2, 'to_subregion_id' => 3],
            ['from_region_id' => 1, 'from_subregion_id' => 4, 'to_region_id' => 2, 'to_subregion_id' => 4],
            ['from_region_id' => 2, 'from_subregion_id' => 1, 'to_region_id' => 3, 'to_subregion_id' => 1],
            ['from_region_id' => 2, 'from_subregion_id' => 2, 'to_region_id' => 3, 'to_subregion_id' => 2],
            ['from_region_id' => 2, 'from_subregion_id' => 3, 'to_region_id' => 3, 'to_subregion_id' => 3],
            ['from_region_id' => 2, 'from_subregion_id' => 4, 'to_region_id' => 3, 'to_subregion_id' => 4],
            ['from_region_id' => 3, 'from_subregion_id' => 1, 'to_region_id' => 1, 'to_subregion_id' => 1],
            ['from_region_id' => 3, 'from_subregion_id' => 2, 'to_region_id' => 1, 'to_subregion_id' => 2],
            ['from_region_id' => 3, 'from_subregion_id' => 3, 'to_region_id' => 1, 'to_subregion_id' => 3],
            ['from_region_id' => 3, 'from_subregion_id' => 4, 'to_region_id' => 1, 'to_subregion_id' => 4],
            ['from_region_id' => 4, 'from_subregion_id' => 1, 'to_region_id' => 4, 'to_subregion_id' => 1],
            ['from_region_id' => 4, 'from_subregion_id' => 2, 'to_region_id' => 4, 'to_subregion_id' => 2],
            ['from_region_id' => 4, 'from_subregion_id' => 3, 'to_region_id' => 4, 'to_subregion_id' => 3],
            ['from_region_id' => 4, 'from_subregion_id' => 4, 'to_region_id' => 4, 'to_subregion_id' => 4],

        ]);
    }
}
