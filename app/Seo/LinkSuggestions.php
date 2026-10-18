<?php

namespace App\Seo;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * SEO-12: published articles worth linking to from the one being edited,
 * scored by shared tags, shared title words and the same category.
 */
class LinkSuggestions
{
    private const STOPWORDS = ['with', 'from', 'your', 'that', 'this', 'into', 'using', 'what', 'when', 'how', 'guide', 'tutorial', 'step', 'steps', 'part'];

    /**
     * @param  list<int|string>  $tagIds
     * @param  array<string, mixed>|null  $body
     * @return Collection<int, array<string, bool|int|string>> title, url, path, linked, reason
     */
    public static function for(?int $exceptId, ?string $title, array $tagIds, ?int $categoryId, ?array $body, int $limit = 6): Collection
    {
        $words = self::keywords((string) $title);
        $tagIds = array_map('intval', $tagIds);
        if (! $words && ! $tagIds && ! $categoryId) {
            return collect();
        }

        $candidates = Article::published()
            ->select(['id', 'type', 'title', 'slug', 'category_id'])
            ->with('tags:id')
            ->when($exceptId, fn (Builder $q) => $q->whereKeyNot($exceptId))
            ->where(fn (Builder $q) => $q
                ->when($tagIds, fn (Builder $q) => $q->orWhereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $tagIds)))
                ->when($categoryId, fn (Builder $q) => $q->orWhere('category_id', $categoryId))
                ->when($words, fn (Builder $q) => $q->orWhere(fn (Builder $q) => collect($words)->each(fn ($w) => $q->orWhere('title', 'like', '%'.$w.'%')))))
            ->latest('published_at')
            ->limit(60)
            ->get();

        $hrefs = self::hrefs($body ?? []);

        // @phpstan-ignore return.type (shape is asserted by the docblock; Collection generics are invariant)
        return $candidates
            ->map(function (Article $article) use ($words, $tagIds, $categoryId, $hrefs) {
                $sharedTags = $article->tags->pluck('id')->intersect($tagIds)->count();
                $sharedWords = count(array_intersect($words, self::keywords($article->title)));
                $path = (string) $article->publicPath();

                return [
                    'score' => $sharedTags * 3 + $sharedWords * 2 + ($categoryId && $article->category_id === $categoryId ? 1 : 0),
                    'title' => $article->title,
                    'url' => url($path),
                    'path' => $path,
                    'linked' => collect($hrefs)->contains(fn ($href) => rtrim(parse_url($href, PHP_URL_PATH) ?: '', '/') === $path),
                    'reason' => collect([$sharedTags ? $sharedTags.' shared '.Str::plural('tag', $sharedTags) : null, $sharedWords ? 'similar title' : null])->filter()->join(', ') ?: 'same category',
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $s) => collect($s)->except('score')->all())
            ->values();
    }

    /** @return list<string> */
    private static function keywords(string $title): array
    {
        return collect(preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($title))) ?: [])
            ->filter(fn ($w) => strlen($w) >= 4 && ! in_array($w, self::STOPWORDS, true))
            ->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    private static function hrefs(array $node): array
    {
        $found = [];
        foreach ($node['marks'] ?? [] as $mark) {
            if (($mark['type'] ?? null) === 'link' && filled($mark['attrs']['href'] ?? null)) {
                $found[] = (string) $mark['attrs']['href'];
            }
        }
        foreach ($node['content'] ?? [] as $child) {
            $found = [...$found, ...self::hrefs($child)];
        }

        return $found;
    }
}
