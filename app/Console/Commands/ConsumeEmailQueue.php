<?php

// app/Console/Commands/ConsumeEmailQueue.php

namespace App\Console\Commands;

use App\Jobs\SampleEmailJob;
use App\Services\RabbitMQService;
use Illuminate\Console\Command;

class ConsumeEmailQueue extends Command
{
    protected $signature = 'rabbitmq:consume';

    protected $description = 'Consume RabbitMQ queue for email sending';

    public function handle(RabbitMQService $rabbitMQService)
    {
        $this->info('Waiting for messages. To exit press CTRL+C');

        $callback = function ($msg) {
            $emailData = json_decode($msg->body, true);
            SampleEmailJob::dispatch($emailData);
            $this->info(" [x] Email job dispatched for {$emailData['to']}");
        };

        $rabbitMQService->consume($callback);
    }
}
