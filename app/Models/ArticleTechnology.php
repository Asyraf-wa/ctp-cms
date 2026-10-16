<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One row of a tutorial's tech stack (ordered; the first is the main technology). Edited by a Filament repeater.
#[Fillable(['article_id', 'technology_id', 'version', 'sort'])]
class ArticleTechnology extends Model
{
    protected $table = 'article_technology';

    public $timestamps = false;

    /** @return BelongsTo<Technology, $this> */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
