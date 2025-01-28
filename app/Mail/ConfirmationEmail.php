<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $name;
    protected $verification_code;
    public $view;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $verification_code, $view = 'email.confirmation')
    {
        $this->name = $name;
        $this->verification_code = $verification_code;
        $this->view = $view;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP Verification Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->view, with: ['name' => $this->name, 'verification_code' =>$this->verification_code]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
