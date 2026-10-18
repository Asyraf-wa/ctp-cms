<?php

namespace App\Mail;

use App\Filament\Resources\Comments\CommentResource;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// CMT-04: tell the owner about a new comment. Reply-to is the commenter.
class NewComment extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function envelope(): Envelope
    {
        $status = $this->comment->status === Comment::APPROVED ? 'New comment' : 'Comment awaiting approval';

        return new Envelope(
            subject: $status.': '.$this->comment->article->title,
            replyTo: [new Address($this->comment->email, $this->comment->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.comments.new', with: [
            'moderateUrl' => CommentResource::getUrl('index', ['tab' => $this->comment->status]),
        ]);
    }
}
