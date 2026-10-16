<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

// Left sidebar of the tutorial layout: categories with a few recent tutorials (template accordion).
class Sidebar
{
    public const PER_CATEGORY = 5;

    /** @return list<array{name: string, icon: string, url: string, count: int, open: bool, items: list<array{title: string, url: string, active: bool}>}> */
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
                        'url' => url($category->publicPath() ?? '/'),
                        'count' => (clone $articles)->count(),
                        'items' => (clone $articles)->latest('published_at')->limit(self::PER_CATEGORY)->get(['id', 'title', 'slug', 'type'])
                            ->map(fn (Article $a) => ['id' => $a->id, 'title' => $a->title, 'url' => url($a->publicPath() ?? '/')])->all(),
                    ];
                })
                ->filter(fn (array $c) => $c['count'] > 0)
                ->values()
                ->all();
        });

        $out = [];
        foreach ($tree as $index => $entry) {
            $isCurrent = ($current && in_array($current->category_id, $entry['ids'], true))
                || ($open && in_array($open->id, $entry['ids'], true));
            $items = array_map(fn (array $i) => ['title' => $i['title'], 'url' => $i['url'], 'active' => $current?->id === $i['id']], $entry['items']);

            // Keep the article being read visible even when it is not among the newest.
            if ($current && $isCurrent && ! collect($items)->contains('active', true)) {
                array_unshift($items, ['title' => $current->title, 'url' => url($current->publicPath() ?? '/'), 'active' => true]);
            }

            $out[] = [
                'name' => $entry['name'],
                'icon' => $entry['icon'],
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
