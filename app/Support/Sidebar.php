<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use App\Models\Technology;
use App\Settings\DisplaySettings;
use Illuminate\Support\Facades\Cache;

// Left sidebar of the tutorial layout: categories with a few recent tutorials (template accordion).
class Sidebar
{
    // The cache keeps up to the maximum; the per-category setting is applied when reading.
    public const CACHED_PER_CATEGORY = DisplaySettings::SIDEBAR_MAX;

    /** @return list<array{name: string, icon: string, technology: ?Technology, url: string, count: int, open: bool, items: list<array{title: string, url: string, active: bool}>}> */
    public static function categories(?Article $current = null, ?Category $open = null): array
    {
        $tree = Cache::remember('sidebar.categories', now()->addMinutes(10), function () {
            return Category::query()->roots()->ordered()->with('children')->get()
                ->map(function (Category $category) {
                    $ids = [$category->id, ...$category->children->pluck('id')];
                    $articles = Article::published()->tutorials()->whereIn('category_id', $ids);

                    return [
                        'id' => $category->id,
                        'ids' => $ids,
                        'name' => $category->name,
                        'icon' => $category->icon ?: 'folder',
                        'technology_id' => $category->technology_id,
                        'url' => url($category->publicPath() ?? '/'),
                        'count' => (clone $articles)->count(),
                        'items' => (clone $articles)->latest('published_at')->limit(self::CACHED_PER_CATEGORY)->get(['id', 'title', 'slug', 'type'])
                            ->map(fn (Article $a) => ['id' => $a->id, 'title' => $a->title, 'url' => url($a->publicPath() ?? '/')])->all(),
                    ];
                })
                ->filter(fn (array $c) => $c['count'] > 0)
                ->values()
                ->all();
        });

        $perCategory = max(1, app(DisplaySettings::class)->sidebar_items_per_category);
        // Logos are not cached as models: one small query per request.
        $technologies = Technology::with('iconMedia')->findMany(array_filter(array_column($tree, 'technology_id')))->keyBy('id');

        $out = [];
        foreach ($tree as $index => $entry) {
            $isCurrent = ($current && in_array($current->category_id, $entry['ids'], true))
                || ($open && in_array($open->id, $entry['ids'], true));
            $items = array_map(fn (array $i) => ['title' => $i['title'], 'url' => $i['url'], 'active' => $current?->id === $i['id']], array_slice($entry['items'], 0, $perCategory));

            // Keep the article being read visible even when it is not among the newest.
            if ($current && $isCurrent && ! collect($items)->contains('active', true)) {
                array_unshift($items, ['title' => $current->title, 'url' => url($current->publicPath() ?? '/'), 'active' => true]);
            }

            $out[] = [
                'name' => $entry['name'],
                'icon' => $entry['icon'],
                'technology' => $technologies->get($entry['technology_id'] ?? 0),
                'url' => $entry['url'],
                'count' => $entry['count'],
                // Open the reader's category; with no article, open the first one (as in the template).
                'open' => $isCurrent || ($current === null && $open === null && $index === 0),
                'items' => $items,
            ];
        }

        return $out;
    }

    public static function flush(): void
    {
        Cache::forget('sidebar.categories');
    }
}
