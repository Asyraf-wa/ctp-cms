<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// CMT-04: someone replied to a comment whose author asked to be told.
class CommentReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Comment $reply, public Comment $parent) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->reply->name.' replied to your comment on '.$this->reply->article->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.comments.reply', with: [
            'unsubscribeUrl' => route('comments.unsubscribe', $this->parent->unsubscribe_token),
        ]);
    }
}
