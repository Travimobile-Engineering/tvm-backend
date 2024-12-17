<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ["id" => 1, "name" => "Admin",],
            ["id" => 2, "name" => "Manager",],
            ["id" => 3, "name" => "Staff",]
        ]);

        DB::table('role_user')->insert([
            'role_id' => 1,
            'user_id' => 1
        ]);
    }
}
