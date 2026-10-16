<?php

namespace App\Mail;

use App\Models\ContactReply;
use App\Settings\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// CONTACT-06: an answer written in the admin inbox. Replies to it come back to the owner's address.
class ContactReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactReply $reply) {}

    public function envelope(): Envelope
    {
        $general = app(GeneralSettings::class);
        $replyTo = $general->contact_email ?: $general->notification_email;

        return new Envelope(
            subject: 'Re: your message to '.$general->site_name,
            replyTo: $replyTo ? [new Address($replyTo, $this->reply->user?->authorName() ?? $general->site_name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact.reply', with: ['site' => app(GeneralSettings::class)->site_name]);
    }
}
