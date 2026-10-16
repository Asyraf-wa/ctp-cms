<?php

namespace App\Models;

use App\Models\Contracts\HasPublicPath;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// PRD MENU-02..04. Content links are stored by ID, so they follow slug changes.
#[Fillable(['menu_id', 'parent_id', 'sort', 'label', 'type', 'linkable_type', 'linkable_id', 'url', 'route', 'target', 'icon', 'badge', 'css_class', 'is_enabled'])]
class MenuItem extends Model
{
    // Built-in sections. Paths, not route names, because some sections arrive in later phases.
    public const ROUTES = [
        'home' => ['Home', '/'],
        'tutorials' => ['All tutorials', '/tutorials'],
        'series' => ['Series', '/series'],
        'blog' => ['Blog', '/blog'],
        'projects' => ['Projects', '/projects'],
        'search' => ['Search', '/search'],
        'contact' => ['Contact', '/contact'],
        'feed' => ['RSS feed', '/feed'],
    ];

    // Content types an item can point at (morph alias => label). Articles, categories, tags, series
    // and projects are added here as their models land in Phase 1.
    public const CONTENT_TYPES = [
        'page' => 'Page',
    ];

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item) {
            // Children inherit the menu of their parent (nested repeater only knows the parent).
            if ($item->parent_id && ! $item->menu_id) {
                $item->menu_id = static::whereKey($item->parent_id)->value('menu_id');
            }
            if (array_key_exists($item->type, self::CONTENT_TYPES)) {
                $item->linkable_type = $item->type;
            } else {
                $item->linkable_type = null;
                $item->linkable_id = null;
            }
        });
        static::saved(fn () => Menu::flushCache());
        static::deleted(fn () => Menu::flushCache());
    }

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return HasMany<MenuItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort');
    }

    /** @return MorphTo<Model, $this> */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function href(): ?string
    {
        return match (true) {
            $this->type === 'url' => blank($this->url) ? null : (str_starts_with($this->url, '/') ? url($this->url) : $this->url),
            $this->type === 'route' => isset(self::ROUTES[$this->route]) ? url(self::ROUTES[$this->route][1]) : null,
            default => $this->contentHref(),
        };
    }

    public function isBroken(): bool
    {
        return array_key_exists($this->type, self::CONTENT_TYPES) && $this->contentHref() === null;
    }

    private function contentHref(): ?string
    {
        $model = $this->linkable;

        if (! $model instanceof HasPublicPath || ! $model->isPubliclyVisible()) {
            return null;
        }

        $path = $model->publicPath();

        return $path === null ? null : url($path);
    }
}
