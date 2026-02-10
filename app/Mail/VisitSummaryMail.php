<?php

namespace App\Mail;

use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VisitSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Visit $visit,
    ) {}

    public function envelope(): Envelope
    {
        $practitioner = $this->visit->practitioner;
        $drName = $practitioner
            ? "Dr. {$practitioner->first_name} {$practitioner->last_name}"
            : 'your doctor';

        return new Envelope(
            subject: "Your Visit Summary — {$drName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.visit-summary',
        );
    }
}
