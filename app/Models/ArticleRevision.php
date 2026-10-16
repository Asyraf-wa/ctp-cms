<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// PRD ART-03.
#[Fillable(['article_id', 'user_id', 'title', 'excerpt', 'tldr', 'body', 'is_autosave'])]
class ArticleRevision extends Model
{
    public const KEEP = 20;

    protected function casts(): array
    {
        return [
            'tldr' => 'array',
            'body' => 'array',
            'is_autosave' => 'boolean',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
