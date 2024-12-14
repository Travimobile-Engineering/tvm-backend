<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            _UserSeeder::class,
            StatesSeeder::class,
            RoutesSeeder::class,
            TransitCompanySeeder::class,
            VehicleBrandsSeeder::class,
            VehicleTypeSeeder::class,
            VehicleSeeder::class,
            TripsSeeder::class,
            FontAwesomeIconsSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            PermissionRoleSeeder::class,
            HaveQuestionSeeder::class,
        ]);
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
