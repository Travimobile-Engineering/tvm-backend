<?php

namespace App\Services\Email;

use App\Jobs\ProcessMail;

class MailingService
{
    public function sendEmails(int $batchSize = 15)
    {
        dispatch(new ProcessMail($batchSize));
    }
}
