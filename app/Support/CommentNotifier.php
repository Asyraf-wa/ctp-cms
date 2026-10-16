<?php

namespace App\Support;

use App\Mail\CommentReply;
use App\Mail\NewComment;
use App\Models\Comment;
use App\Settings\GeneralSettings;
use App\Settings\IntegrationSettings;
use Illuminate\Support\Facades\Mail;

// CMT-04: who hears about a comment, and when.
class CommentNotifier
{
    // A reader's comment arrived (approved or waiting). Spam is never mailed.
    public static function created(Comment $comment): void
    {
        $to = self::ownerAddress();
        if ($comment->status !== Comment::SPAM && ! $comment->user_id && $to && app(IntegrationSettings::class)->comments_notify_admin) {
            Mail::to($to)->queue(new NewComment($comment));
        }
        if ($comment->status === Comment::APPROVED) {
            self::approved($comment);
        }
    }

    // Visible now: tell the parent's author if they asked (never themselves).
    public static function approved(Comment $comment): void
    {
        $parent = $comment->parent;
        if ($parent && $parent->notify_replies && $parent->status === Comment::APPROVED && $parent->email !== $comment->email) {
            Mail::to($parent->email, $parent->name)->queue(new CommentReply($comment, $parent));
        }
    }

    public static function ownerAddress(): ?string
    {
        $general = app(GeneralSettings::class);

        return $general->notification_email ?: $general->contact_email;
    }
}
