<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['id' => 1, 'name' => 'permission_access'],
            ['id' => 2, 'name' => 'permission_create'],
            ['id' => 3, 'name' => 'permission_view'],
            ['id' => 4, 'name' => 'permission_edit'],
            ['id' => 5, 'name' => 'permission_delete'],
            ['id' => 6, 'name' => 'role_access'],
            ['id' => 7, 'name' => 'role_create'],
            ['id' => 8, 'name' => 'role_view'],
            ['id' => 9, 'name' => 'role_edit'],
            ['id' => 10, 'name' => 'role_delete'],
        ]);
    }
}
