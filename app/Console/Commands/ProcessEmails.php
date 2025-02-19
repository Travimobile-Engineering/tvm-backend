<?php

namespace App\Console\Commands;

use App\Services\Email\MailingService;
use Illuminate\Console\Command;

class ProcessEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending emails in batch';

    /**
     * Execute the console command.
     */
    public function handle(MailingService $emailService)
    {
        $emailService->sendEmails(15);
        $this->info('Emails processed successfully.');
    }
}
