<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleSeeder extends Seeder
{
    /***
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicles')->insert([
            ['name' => 'Toyota', 'company_id' => '4', 'brand_id' => '1', 'year' => '2018', 'model' => 'Sienna', 'plate_no' => 'Eky-445-PHC', 'engine_no' => '9783787893787834', 'chassis_no' => '0989347878839783', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Honda', 'company_id' => '7', 'brand_id' => '3', 'year' => '2018', 'model' => 'Odyssey', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Ford Transit', 'company_id' => '2', 'brand_id' => '2', 'year' => '2018', 'model' => 'Ford Transit', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Chevrolet', 'company_id' => '5', 'brand_id' => '4', 'year' => '2018', 'model' => 'Express', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Nissan', 'company_id' => '3', 'brand_id' => '9', 'year' => '2018', 'model' => 'NV350 Urvan', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Mercedes-Benz', 'company_id' => '8', 'brand_id' => '6', 'year' => '2018', 'model' => 'Sprinter', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Toyota', 'company_id' => '1', 'brand_id' => '1', 'year' => '2018', 'model' => 'Coaster', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Hyundai', 'company_id' => '10', 'brand_id' => '8', 'year' => '2018', 'model' => 'County', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Volkswagen', 'company_id' => '6', 'brand_id' => '7', 'year' => '2018', 'model' => 'Transporter', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]'],
            ['name' => 'Mercedes-Benz', 'company_id' => '9', 'brand_id' => '6', 'year' => '2018', 'model' => 'Citaro', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A3","A4"]']
        ]);
    }
}
