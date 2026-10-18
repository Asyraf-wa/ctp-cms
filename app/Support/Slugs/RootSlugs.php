<?php

namespace App\Support\Slugs;

use App\Models\RootSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * D9 / ARCH §6: the shared domain/{slug} namespace for tutorials and pages.
 */
class RootSlugs
{
    // Planned sections (ARCH §6) and words worth keeping free, even before their routes exist.
    public const ALWAYS_RESERVED = [
        'admin', 'api', 'auth', 'login', 'logout', 'register', 'account', 'dashboard',
        'tutorials', 'tutorial', 'category', 'categories', 'tag', 'tags', 'series', 'blog', 'projects', 'project',
        'author', 'authors', 'contact', 'search', 'feed', 'rss', 'sitemap', 'llms', 'og', 'media', 'uploads',
        'docs', 'shop', 'courses', 'preview', 'livewire', 'filament', 'storage', 'build', 'vendor', 'up',
    ];

    /** @var list<string>|null */
    private static ?array $reserved = null;

    /** @return list<string> */
    public static function reserved(): array
    {
        if (self::$reserved !== null) {
            return self::$reserved;
        }

        $fromRoutes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => strtolower(explode('/', trim($route->uri(), '/'))[0]))
            ->reject(fn (string $segment) => $segment === '' || str_contains($segment, '{'));

        $publicDirectories = collect(File::directories(public_path()))->map(fn (string $dir) => strtolower(basename($dir)));

        return self::$reserved = collect(self::ALWAYS_RESERVED)
            ->merge($fromRoutes)
            ->merge($publicDirectories)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function flushReserved(): void
    {
        self::$reserved = null;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::reserved(), true);
    }

    public static function isAvailable(string $slug, ?Model $ignore = null): bool
    {
        return ! RootSlug::query()
            ->where('slug', $slug)
            ->when($ignore?->exists, fn ($query) => $query->where(fn ($q) => $q
                ->where('sluggable_type', '!=', $ignore->getMorphClass())
                ->orWhere('sluggable_id', '!=', $ignore->getKey())))
            ->exists();
    }

    public static function resolve(string $slug): ?Model
    {
        return RootSlug::find($slug)?->sluggable;
    }
}
