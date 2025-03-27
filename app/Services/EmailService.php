<?php

// app/Services/EmailService.php

namespace App\Services;

class EmailService
{
    protected $rabbitMQService;

    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }

    public function sendEmail($emailData)
    {
        $this->rabbitMQService->publish(json_encode($emailData));
    }
}
