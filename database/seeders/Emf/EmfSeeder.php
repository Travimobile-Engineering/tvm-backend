<?php

namespace Database\Seeders\Emf;

use Illuminate\Database\Seeder;

class EmfSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EmfReferenceSeeder::class,
            EmfAccessSeeder::class,
            EmfOperationsSeeder::class,
            EmfMetaSeeder::class,
        ]);
    }
}
