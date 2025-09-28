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
        if (! app()->environment('production')) {
            $this->info('This command can only run in production environment.');

            return;
        }

        // $this->processPayout();
    }
}
