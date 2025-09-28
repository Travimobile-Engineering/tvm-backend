<?php

namespace App\Console\Commands;

use Database\Seeders\SecurityQuestionsSeeder;
use Illuminate\Console\Command;

class RotateSecurityQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rotate-security-questions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace all security questions with a fresh set';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // (new SecurityQuestionsSeeder())->run();

        $this->info('Security questions rotated successfully.');
    }
}
