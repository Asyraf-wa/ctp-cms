<?php

namespace App\Models;

use App\Models\Concerns\HasRenderedBody;
use App\Models\Concerns\HasRootSlug;
use App\Models\Concerns\TracksMediaUsage;
use App\Models\Contracts\HasPublicPath;
use Database\Factories\PageFactory;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * PRD PUB-10: static pages at domain/{slug}.
 *
 * @property array<string, mixed>|null $body
 * @property list<array{id: string, label: string, level: int}>|null $toc
 */
#[Fillable(['title', 'slug', 'excerpt', 'body', 'template', 'is_published', 'sort'])]
class Page extends Model implements HasPublicPath, HasRichContent
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasRenderedBody, HasRootSlug, TracksMediaUsage;

    // Layout variants picked per page (T2.11 styles them).
    public const TEMPLATES = [
        'default' => 'Default: narrow reading column',
        'toc' => 'Long read: with “On this page” (Privacy, Terms)',
        'about' => 'About: author bio and social links first',
        'wide' => 'Wide: full content width',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->isDirty('body') || ! $page->exists) {
                $page->renderBody();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'toc' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function renderBody(): void
    {
        $result = $this->renderRichBody();
        $this->body_rendered = $result['html'];
        $this->toc = $result['toc'];
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published;
    }

    /** @return MorphOne<SeoMeta, $this> */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function mediaUsage(): array
    {
        return ['body' => $this->bodyMediaIds()];
    }

    public function mediaUsageLabel(): string
    {
        return 'Page: '.$this->title;
    }
}
