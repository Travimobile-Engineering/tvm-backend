<?php

namespace App\Console\Commands;

use App\Services\Mail\FallbackMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckMailProviders extends Command
{
    protected $signature = 'mail:check-providers
                            {--to= : Email address to send a real test message to each provider}';

    protected $description = 'Ping every configured mail provider and report their status';

    public function handle(FallbackMailer $fallbackMailer): int
    {
        $mailers = $fallbackMailer->getMailers();

        if (empty($mailers)) {
            $this->error('No mailers found in mail.fallback_order.');

            return self::FAILURE;
        }

        $to = $this->option('to');
        $rows = [];

        $this->info('Checking '.count($mailers).' mail provider(s)...');
        $this->newLine();

        foreach ($mailers as $mailerName) {
            $config = config("mail.mailers.{$mailerName}");

            if (! $config) {
                $rows[] = [$mailerName, 'NOT CONFIGURED', 'Missing from config/mail.php'];

                continue;
            }

            if (! $to) {
                // No --to given: config exists, that's all we can verify without sending
                $rows[] = [$mailerName, 'CONFIGURED', 'Pass --to=you@example.com to send a real test'];

                continue;
            }

            try {
                Mail::mailer($mailerName)
                    ->raw(
                        "Test from [{$mailerName}] — ".now()->toDateTimeString(),
                        function ($message) use ($to, $mailerName) {
                            $message->to($to)->subject("[Mail Check] Provider: {$mailerName}");
                        }
                    );

                $rows[] = [$mailerName, 'OK', "Test email delivered to {$to}"];

            } catch (Throwable $e) {
                $rows[] = [$mailerName, 'FAILED', $e->getMessage()];
            }
        }

        $this->table(['Mailer', 'Status', 'Detail'], $rows);

        $failures = collect($rows)->filter(fn ($r) => $r[1] === 'FAILED')->count();

        if ($failures === 0) {
            $this->info('All providers are reachable.');
        } else {
            $this->warn("{$failures} provider(s) failed. Check your credentials or connectivity.");
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
