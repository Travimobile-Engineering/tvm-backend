<?php

namespace Database\Seeders;

use App\Enum\UserType;
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
            ['uuid' => '6983571734087474', 'first_name' => 'Admin', 'last_name' => 'Administrator', 'phone_number' => '012345678910', 'email' => 'admin@travimobile.com', 'password' => '$2y$12$RI393MpvWLf6Kq5JXQyTYOoLbkf7BT90n/aau1fO/mcE6G/waPLAC', 'user_category' => UserType::PASSENGER, 'is_admin' => 1, 'verification_code' => '09458', 'email_verified' => 1, 'email_verified_at' => date('Y-m-d H:i:s')],
            ['uuid' => '6983576734087475', 'first_name' => 'User', 'last_name' => 'Test', 'phone_number' => '112345678900', 'email' => 'user@travimobile.com', 'password' => '$2y$12$RI393MpvWLf6Kq5JXQyTYOoLbkf7BT90n/aau1fO/mcE6G/waPLAC', 'user_category' => UserType::PASSENGER, 'verification_code' => '09458', 'email_verified' => 1, 'email_verified_at' => date('Y-m-d H:i:s')],
            ['uuid' => '6983576734087476', 'first_name' => 'Security', 'last_name' => 'Force', 'phone_number' => '112345678945', 'email' => 'security@travimobile.com', 'password' => '$2y$12$RI393MpvWLf6Kq5JXQyTYOoLbkf7BT90n/aau1fO/mcE6G/waPLAC', 'user_category' => UserType::SECURITY, 'verification_code' => '09458', 'email_verified' => 1, 'email_verified_at' => date('Y-m-d H:i:s')],
        ]);
    }
}
