<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SecurityQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pool = config('questions');

        $selected = collect($pool)->shuffle()->take(20)->map(function ($q) {
            return [
                'question' => $q,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        DB::table('security_questions')->truncate();
        DB::table('security_questions')->insert($selected->toArray());
    }
}
