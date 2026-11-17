<?php

namespace Database\Seeders\Npis;

use Illuminate\Database\Seeder;

class NpisDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            NpisReferenceSeeder::class,
            NpisProtocolSeeder::class,
            NpisEventSeeder::class,
            NpisAdminSeeder::class,
            NpisProgrammeSeeder::class,
        ]);
    }
}
