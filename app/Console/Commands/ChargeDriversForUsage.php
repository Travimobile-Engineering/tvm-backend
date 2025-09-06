<?php

namespace App\Console\Commands;

use App\Services\ERP\DriverUsageService;
use Illuminate\Console\Command;

class ChargeDriversForUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drivers:charge-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge drivers for usage based on completed trips today';

    /**
     * Execute the console command.
     */
    public function handle(DriverUsageService $driverUsageService)
    {
        if (!app()->environment('production')) {
            $this->info('This command can only run in production environment.');
            return;
        }

        $this->info('Charging drivers for usage...');
        $driverUsageService->execute();
        $this->info('Charging complete!');
    }
}
