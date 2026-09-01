<?php

namespace App\Mail;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the registry when a student files a case.
 *
 * The default mailer is the log driver, so this works on a stock XAMPP install
 * with no SMTP credentials: the message is written to storage/logs/laravel.log
 * where it can be shown during a demonstration. Point MAIL_MAILER at smtp to
 * deliver it for real.
 */
class ComplaintFiled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Complaint $complaint) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New complaint {$this->complaint->reference}: {$this->complaint->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.complaint-filed');
    }
}
