<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// CONTACT-05: confirms receipt to the sender with a copy of their message and the reply time.
class ContactAutoReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We got your message · '.app(GeneralSettings::class)->site_name);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact.auto-reply', with: [
            'replyTime' => app(ContactSettings::class)->reply_time,
            'site' => app(GeneralSettings::class)->site_name,
        ]);
    }
}
