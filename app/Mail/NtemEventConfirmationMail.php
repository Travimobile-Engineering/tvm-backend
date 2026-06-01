<?php

namespace App\Mail;

use App\Models\NtemEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NtemEventConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The reference ID is derived from the DB record as "REG-ABUJA-{id}"
     * and pre-computed here so the view receives a clean, ready-to-display value.
     */
    public string $referenceId;

    public function __construct(protected NtemEvent $ntemEvent)
    {
        $this->referenceId = 'REG-ABUJA-'.$ntemEvent->id;
    }

    /**
     * Define the envelope (sender, subject).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@travimobile.com', 'NTEM Event Registration'),
            subject: 'Registration Confirmed – '.$this->referenceId,
        );
    }

    /**
     * Define the view and the data passed to it.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.ntem-event-confirmation',
            with: [
                'fullName' => $this->ntemEvent->full_name,
                'referenceId' => $this->referenceId,
                'email' => $this->ntemEvent->email,
                'organization' => $this->ntemEvent->organization,
                'jobTitle' => $this->ntemEvent->job_title,
                'state' => $this->ntemEvent->state,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
