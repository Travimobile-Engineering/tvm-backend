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
            ['name' => 'Toyota Sienna', 'company_id' => '4', 'brand_id' => '1', 'type_id' => '2', 'plate_no' => 'Eky-445-PHC', 'engine_no' => '9783787893787834', 'chassis_no' => '0989347878839783', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Honda Odyssey', 'company_id' => '7', 'brand_id' => '3', 'type_id' => '2', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Ford Transit', 'company_id' => '2', 'brand_id' => '2', 'type_id' => '3', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Chevrolet Express', 'company_id' => '5', 'brand_id' => '4', 'type_id' => '3', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Nissan NV350 Urvan', 'company_id' => '3', 'brand_id' => '9', 'type_id' => '3', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Mercedes-Benz Sprinter', 'company_id' => '8', 'brand_id' => '6', 'type_id' => '3', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Toyota Coaster', 'company_id' => '1', 'brand_id' => '1', 'type_id' => '1', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Hyundai County', 'company_id' => '10', 'brand_id' => '8', 'type_id' => '4', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Volkswagen Transporter', 'company_id' => '6', 'brand_id' => '7', 'type_id' => '3', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]'],
            ['name' => 'Mercedes-Benz Citaro', 'company_id' => '9', 'brand_id' => '6', 'type_id' => '5', 'plate_no' => 'ABC-784-XYZ', 'engine_no' => '88347889378893345', 'chassis_no' => '8989347878389878348989', 'color' => '#000000', 'seats' => '["B1","C1","C2","D1","D4","E1","E2","E3","E4","B2","C4","D2","A2","A3"]']
        ]);
    }
}
