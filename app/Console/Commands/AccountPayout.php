<?php

namespace App\Console\Commands;

use App\Services\Admin\AccountService;
use App\Trait\Transfer;
use Illuminate\Console\Command;

class AccountPayout extends Command
{
    use Transfer;

    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        parent::__construct();
        $this->accountService = $accountService;
    }

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
        $this->processPayout($this->accountService);
    }

    private function processPayout($accountService)
    {
        $this->info('Processing payout(s)...');
        $this->processAccumulatedTransfers($accountService);
        $this->info('Payout(s) processing done.');
    }
}
