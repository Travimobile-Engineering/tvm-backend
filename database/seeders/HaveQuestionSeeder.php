<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HaveQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cms_have_questions')->insert([
            ['name' => 'Isijola Temidayo', 'email' => 'temidayoisijola@gmail.com', 'subject' => 'Help with setting up the passengers app', 'message' => 'Dear sir, please could you help set up my application'],
            ['name' => 'Adu Dipo', 'email' => 'dipoadu@gmail.com', 'subject' => 'How can i get your application making waves', 'message' => 'Dear sir/ma, your Travi application is the talk of the city, please how can i get it'],
            ['name' => 'John Helen', 'email' => 'helen.j@yahoo.com', 'subject' => 'This application is worth it', 'message' => 'Dear sir, I just want to say your team had done wonderfully well on this passengers app'],
        ]);
    }
}
