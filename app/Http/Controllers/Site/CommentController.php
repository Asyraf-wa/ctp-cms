<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Support\CommentNotifier;
use App\Support\Spam\SpamGuard;
use App\Support\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CMT-01, CMT-02: comments are posted with fetch() from cached article pages, so there is no CSRF token.
 * Origin check, Turnstile, honeypot, time-to-submit, link and keyword rules and a rate limit stand in.
 */
class CommentController extends Controller
{
    public function store(Request $request, Article $article): JsonResponse
    {
        abort_unless(Visitor::isSameOrigin($request), 403);
        abort_unless($article->commentsOpen(), 403, 'Comments are closed.');

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'notify_replies' => ['boolean'],
            // Replies are one level deep: only to an approved top-level comment of this article.
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('article_id', $article->id)->whereNull('parent_id')->where('status', Comment::APPROVED)],
        ], [
            'body.min' => 'Write a little more.',
            'parent_id.exists' => 'That comment can no longer be replied to.',
        ]);

        $reason = SpamGuard::check($request, $data['body'].' '.$data['name']);
        if ($reason === 'turnstile') {
            return response()->json(['message' => 'Please complete the “I am human” check and try again.'], 422);
        }
        if (in_array($reason, SpamGuard::SILENT, true)) {
            // Looks like it worked; nothing is stored.
            return response()->json(['status' => Comment::PENDING, 'message' => $this->pendingMessage()]);
        }

        $status = $reason ? Comment::SPAM : (Comment::isTrusted($data['email']) ? Comment::APPROVED : Comment::PENDING);

        $comment = $article->comments()->create([
            ...$data,
            'notify_replies' => (bool) ($data['notify_replies'] ?? false),
            'status' => $status,
            'spam_reason' => $reason,
            'visitor_hash' => Visitor::hash($request),
        ]);

        CommentNotifier::created($comment);

        return $status === Comment::APPROVED
            ? response()->json(['status' => $status, 'message' => 'Thanks! Your comment is live.', 'id' => $comment->id,
                'html' => view('components.comments.item', ['comment' => $comment->load('user'), 'replyable' => false])->render()])
            : response()->json(['status' => Comment::PENDING, 'message' => $this->pendingMessage()]);
    }

    // CMT-04: one click from the reply email stops reply emails for that address.
    public function unsubscribe(string $token): View
    {
        $comment = Comment::where('unsubscribe_token', $token)->firstOrFail();
        Comment::where('email', $comment->email)->update(['notify_replies' => false]);

        return view('site.message', [
            'title' => 'Unsubscribed',
            'heading' => 'No more reply emails',
            'message' => 'You will not get emails about replies to your comments any more. You can turn them back on when you next comment.',
            'link' => [$comment->article->title, url($comment->article->publicPath() ?? '/')],
        ]);
    }

    private function pendingMessage(): string
    {
        return 'Thanks! Your comment will appear after a quick review.';
    }
}
