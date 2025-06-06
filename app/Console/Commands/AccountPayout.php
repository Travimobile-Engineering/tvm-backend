<?php

namespace App\Console\Commands;

use App\Trait\Transfer;
use Illuminate\Console\Command;

class AccountPayout extends Command
{
    use Transfer;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:account-payout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Payout system for account transfer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->processPayout();
    }

    private function processPayout()
    {
        $this->info('Processing payout(s)...');

        $requests = $this->collectRequests();

        if (empty($requests)) {
            $this->info('No transfers(s) to process.');
            return;
        }

        foreach (array_chunk($requests, 100) as $chunk) {
            $this->handleChunk($chunk);
        }

        $this->info('Payout(s) processing done.');
    }
}
