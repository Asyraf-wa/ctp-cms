<?php

namespace App\Search;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * PRD PUB-06: MySQL FULLTEXT search over published articles (title, excerpt, body_text),
 * plus name matches on categories and tags. Works on shared hosting: no external engine.
 *
 * Terms of three or more characters go to MATCH … AGAINST in boolean mode as required prefixes
 * ("+auth*"), which covers search-as-you-type. Shorter terms (innodb_ft_min_token_size is 3)
 * become whole-word REGEXP filters on the title and excerpt, so "js" or "ui" still narrow the results.
 */
class Search
{
    public const MAX_LENGTH = 100;

    public static function normalise(?string $query): string
    {
        return Str::of((string) $query)->squish()->limit(self::MAX_LENGTH, '')->value();
    }

    /** @return array{fulltext: list<string>, short: list<string>} */
    public static function terms(string $query): array
    {
        // Drop boolean-mode operators so user input can never change the query's meaning.
        $words = preg_split('/\s+/u', trim((string) preg_replace('/[+\-<>()~*"@]+/u', ' ', $query)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_unique(array_map(fn ($w) => mb_strtolower($w), $words)));

        $fulltext = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 3));
        $short = array_values(array_filter($words, fn ($w) => mb_strlen($w) < 3));

        return ['fulltext' => array_slice($fulltext, 0, 8), 'short' => array_slice($short, 0, 4)];
    }

    /** @return Builder<Article>|null null when the query has nothing to search for */
    public function articleQuery(string $query): ?Builder
    {
        $terms = self::terms($query);
        if (! $terms['fulltext'] && ! $terms['short']) {
            return null;
        }

        $builder = Article::published()->forCards();

        if ($terms['fulltext']) {
            $against = implode(' ', array_map(fn ($t) => '+'.$t.'*', $terms['fulltext']));
            $builder->whereFullText(['title', 'excerpt', 'body_text'], $against, ['mode' => 'boolean'])
                // Relevance, with a strong boost when the phrase is in the title.
                ->selectRaw('(MATCH(title, excerpt, body_text) AGAINST (? IN BOOLEAN MODE) + (title LIKE ?) * 10) AS relevance', [$against, '%'.self::escapeLike($query).'%'])
                ->orderByDesc('relevance');
        }

        // Whole words only: "ui" must not match "build" or "quick".
        foreach ($terms['short'] as $term) {
            $pattern = '\\b'.preg_quote($term).'\\b';
            $builder->where(fn (Builder $q) => $q
                ->where('title', 'regexp', $pattern)
                ->orWhere('excerpt', 'regexp', $pattern));
        }

        return $builder->orderByDesc('is_pinned')->latest('published_at');
    }

    /** @return LengthAwarePaginator<int, Article>|null */
    public function paginate(string $query, int $perPage = 10): ?LengthAwarePaginator
    {
        return $this->articleQuery($query)?->paginate($perPage)->withQueryString();
    }

    /**
     * Grouped, capped results for the Ctrl/⌘ K palette.
     *
     * @return array{tutorials: Collection<int, Article>, blog: Collection<int, Article>, categories: Collection<int, Category>, tags: Collection<int, Tag>}
     */
    public function quick(string $query): array
    {
        $articles = $this->articleQuery($query);
        $like = '%'.self::escapeLike($query).'%';

        return [
            'tutorials' => $articles ? (clone $articles)->tutorials()->limit(6)->get() : new Collection,
            'blog' => $articles ? (clone $articles)->blogPosts()->limit(3)->get() : new Collection,
            'categories' => mb_strlen($query) >= 2 ? Category::where('name', 'like', $like)->orderBy('name')->limit(3)->get() : new Collection,
            'tags' => mb_strlen($query) >= 2 ? Tag::where('name', 'like', $like)
                ->whereHas('articles', fn ($q) => $q->published())
                ->orderBy('name')->limit(4)->get() : new Collection,
        ];
    }

    /**
     * Escape the text, then wrap each search term in <mark>.
     */
    public static function highlight(?string $text, string $query): string
    {
        $html = e((string) $text);
        $terms = array_merge(...array_values(self::terms($query)));
        if (! $terms || $html === '') {
            return $html;
        }

        $pattern = '/('.implode('|', array_map(fn ($t) => preg_quote(e($t), '/'), $terms)).')/iu';

        return (string) preg_replace($pattern, '<mark>$1</mark>', $html);
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
