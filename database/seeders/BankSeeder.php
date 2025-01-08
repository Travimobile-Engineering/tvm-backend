<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Services\Curl\GetCurlService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Bank::count() === 0) {
            $url = config('services.paystack_base_url');
            $token = config('app.paystack_secret_key');

            $headers = [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ];

            $banks = (new GetCurlService($url, $headers))->execute();

            foreach ($banks as $bank) {
                Bank::create([
                    'name' => $bank['name'],
                    'slug' => $bank['slug'],
                    'code' => $bank['code'],
                    'currency' => $bank['currency'],
                ]);
            }
        } else {
            $this->command->info('Bank table is not empty. Skipping seeding.');
        }
    }
}
