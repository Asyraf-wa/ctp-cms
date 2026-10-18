<?php

namespace App\Models;

use App\Support\Html;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * PRD CMT-01..05: reader comments, one level of replies. Replies from the admin carry user_id (Author badge).
 *
 * @property Carbon|null $approved_at
 */
#[Fillable(['article_id', 'parent_id', 'user_id', 'name', 'email', 'body', 'status', 'notify_replies', 'visitor_hash', 'spam_reason', 'approved_at'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const SPAM = 'spam';

    public const TRASH = 'trash';

    public const STATUSES = [self::PENDING => 'Pending', self::APPROVED => 'Approved', self::SPAM => 'Spam', self::TRASH => 'Trash'];

    protected static function booted(): void
    {
        static::saving(function (Comment $comment) {
            if ($comment->isDirty('body')) {
                $comment->body_html = Html::comment($comment->body);
            }
            if ($comment->isDirty('status')) {
                $comment->approved_at = $comment->status === self::APPROVED ? ($comment->approved_at ?? now()) : null;
            }
            if ($comment->notify_replies && ! $comment->unsubscribe_token) {
                // Lowercase: URL normalisation lowercases paths (SEO-10).
                $comment->unsubscribe_token = Str::lower(Str::random(40));
            }
            $comment->email = Str::lower(trim($comment->email));
        });
    }

    protected function casts(): array
    {
        return ['notify_replies' => 'boolean', 'approved_at' => 'datetime'];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<Comment, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /** @return HasMany<Comment, $this> approved replies, oldest first */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('status', self::APPROVED)->oldest();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<Comment> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::APPROVED);
    }

    public function isByAuthor(): bool
    {
        return $this->user_id !== null;
    }

    public function anchor(): string
    {
        return 'comment-'.$this->id;
    }

    public function url(): string
    {
        return url($this->article->publicPath() ?? '/').'#'.$this->anchor();
    }

    // Gravatar from the (never displayed) email; the admin's own avatar for author replies.
    public function avatarUrl(int $size = 80): string
    {
        if ($this->user?->avatarUrl()) {
            return $this->user->avatarUrl();
        }

        return 'https://www.gravatar.com/avatar/'.md5($this->email).'?s='.$size.'&d=identicon&r=g';
    }

    // CMT-02: someone with an approved comment is trusted next time.
    public static function isTrusted(string $email): bool
    {
        return static::where('email', Str::lower(trim($email)))->where('status', self::APPROVED)->whereNull('user_id')->exists();
    }
}
