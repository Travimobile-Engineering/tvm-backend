<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            ['uuid' => '6983571734087474', 'first_name' => 'Admin', 'last_name' => 'Administrator', 'phone_number' => '012345678910', 'email' => 'admin@travimobile.com', 'password' => '$2y$10$C8gijn/TMfiFqepFvVIldOTyEQ5K9lf3ZJoNaUhotkYdKbZ23pDyy', 'verification_code' => '09458']
        ]);
    }
}
