<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksMediaUsage;
use App\Support\Contrast;
use App\Support\Sidebar;
use App\Support\SimpleIcons;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * A technology with a logo (Laravel, PHP, CSS…). Tutorials list theirs in order (the first is the main one,
 * shown in lists); projects use them as their stack; a category can borrow one as its icon.
 * Logos show in grey and take the brand colour on hover (DESIGN: tech icons).
 */
#[Fillable(['name', 'slug', 'icon_type', 'icon', 'icon_media_id', 'color', 'website_url'])]
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory, HasSlug, TracksMediaUsage;

    public const TYPES = ['simple' => 'Brand logo', 'media' => 'Upload SVG', 'lucide' => 'Generic icon'];

    // Card background in dark mode: brand colours must stay visible against it.
    private const DARK_SURFACE = '#111a2e';

    protected static function booted(): void
    {
        static::saving(function (Technology $technology) {
            // A brand logo brings its own colour unless one was chosen.
            if ($technology->icon_type === 'simple' && blank($technology->color)) {
                $technology->color = SimpleIcons::find($technology->icon)['hex'] ?? null;
            }
        });
        static::saved(fn () => Sidebar::flush());
        static::deleted(fn () => Sidebar::flush());
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    // No public page yet (/tech/{slug} may come later).
    public function pathForSlug(string $slug): ?string
    {
        return null;
    }

    /** @return BelongsTo<Media, $this> */
    public function iconMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'icon_media_id');
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withPivot(['version', 'sort']);
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /** The logo as inline SVG (so CSS can recolour it), or an <img> for uploaded files. */
    public function iconHtml(string $class = 'size-4'): HtmlString
    {
        try {
            $html = match ($this->icon_type) {
                'simple' => svg('si-'.$this->icon, $class, ['fill' => 'currentColor', 'aria-hidden' => 'true'])->toHtml(),
                'media' => $this->iconMedia
                    ? '<img src="'.e($this->iconMedia->url()).'" alt="" class="'.e($class).'" loading="lazy" decoding="async">'
                    : null,
                default => svg('lucide-'.($this->icon ?: 'code-xml'), $class, ['aria-hidden' => 'true'])->toHtml(),
            };
        } catch (Throwable) {
            $html = null; // an icon removed from the package must not break the page
        }

        return new HtmlString($html ?? svg('lucide-code-xml', $class, ['aria-hidden' => 'true'])->toHtml());
    }

    public function brandColor(): ?string
    {
        return $this->color ?: null;
    }

    // The brand colour, lightened until it reads on the dark theme (≥ 3:1, the WCAG minimum for graphics).
    public function darkColor(): ?string
    {
        $hex = $this->brandColor();
        if (! $hex) {
            return null;
        }

        [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        for ($step = 0; $step <= 10; $step++) {
            $mix = fn (int $c) => (int) round($c + (255 - $c) * $step / 10);
            $candidate = sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
            if (Contrast::ratio(Contrast::luminance($candidate), Contrast::luminance(self::DARK_SURFACE)) >= 3) {
                return $candidate;
            }
        }

        return '#e2e8f0';
    }

    public function mediaUsage(): array
    {
        return ['icon' => [$this->icon_type === 'media' ? $this->icon_media_id : null]];
    }

    public function mediaUsageLabel(): string
    {
        return 'Technology: '.$this->name;
    }
}
