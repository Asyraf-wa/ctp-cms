<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * PRD CONTACT-01..07: a message from the contact page and the replies sent from the admin.
 *
 * @property Carbon|null $consented_at
 * @property Carbon|null $read_at
 * @property Carbon|null $replied_at
 */
#[Fillable(['topic', 'name', 'email', 'company', 'article_id', 'message', 'status', 'spam_reason', 'visitor_hash', 'consented_at', 'read_at', 'replied_at'])]
class ContactMessage extends Model
{
    use Prunable;

    public const NEW = 'new';

    public const READ = 'read';

    public const REPLIED = 'replied';

    public const ARCHIVED = 'archived';

    public const SPAM = 'spam';

    public const STATUSES = [self::NEW => 'New', self::READ => 'Read', self::REPLIED => 'Replied', self::ARCHIVED => 'Archived', self::SPAM => 'Spam'];

    /**
     * CONTACT-02: topics, with the icon and line shown on the topic cards.
     * `company` and `article` say which extra fields the topic shows.
     */
    public const TOPICS = [
        'general' => ['label' => 'General', 'icon' => 'message-circle', 'line' => 'Say hello or ask anything else.'],
        'tutorial' => ['label' => 'Tutorial question', 'icon' => 'book-open', 'line' => 'Stuck on a step? Tell me which tutorial.', 'article' => true],
        'collaboration' => ['label' => 'Collaboration / Hire', 'icon' => 'handshake', 'line' => 'Projects, consulting or freelance work.', 'company' => true],
        'speaking' => ['label' => 'Speaking / Workshop', 'icon' => 'presentation', 'line' => 'Talks, classes and workshops.', 'company' => true],
        'problem' => ['label' => 'Report a problem', 'icon' => 'bug', 'line' => 'A broken link, a typo or wrong code.', 'article' => true],
        'other' => ['label' => 'Other', 'icon' => 'ellipsis', 'line' => 'Something that does not fit above.'],
    ];

    protected static function booted(): void
    {
        static::saving(fn (ContactMessage $message) => $message->email = Str::lower(trim($message->email)));
    }

    protected function casts(): array
    {
        return ['consented_at' => 'datetime', 'read_at' => 'datetime', 'replied_at' => 'datetime'];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return HasMany<ContactReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class)->oldest();
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic]['label'] ?? Str::headline($this->topic);
    }

    public function markRead(): void
    {
        if ($this->status === self::NEW) {
            $this->update(['status' => self::READ, 'read_at' => now()]);
        }
    }

    // CONTACT-07: messages (and their replies) are deleted 12 months after they arrive.
    /** @return Builder<ContactMessage> */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(12));
    }
}
