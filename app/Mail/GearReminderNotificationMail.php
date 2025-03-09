<?php

namespace App\Mail;

use App\Models\GearReminder;
use App\Models\StravaActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GearReminderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GearReminder $reminder, public StravaActivity $stravaActivity)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Gear Reminder for {$this->reminder->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.gear_reminder_notification',
        );
    }
}
