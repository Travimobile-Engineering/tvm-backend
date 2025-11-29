<?php

namespace App\Jobs;

use App\Mail\ConfirmationEmail;
use App\Mail\SampleMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SampleEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $emailData;

    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->emailData['to'])
        // ->send(new ConfirmationEmail($this->emailData['name'], $this->emailData['code'],$this->emailData['view']));
            ->send(new SampleMail($this->emailData['name'], $this->emailData['code']));
    }
}
